<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Product;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class AssetShow extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public ?string $returnTo = null;

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;
        $this->returnTo = $this->sanitizeReturnTo(request()->query('returnTo'));

        $this->productModel = Product::query()
            ->with(['category', 'brand'])
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->loadAssetModel();
    }

    #[On('inventory:asset-changed')]
    public function onAssetChanged(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }

        $this->loadAssetModel();
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.assets.asset-show', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
            'returnTo' => $this->returnTo,
            'overviewCards' => $this->buildOverviewCards(),
            'lifecycleCards' => $this->buildLifecycleCards(),
            'headerCounts' => $this->buildHeaderCounts(),
            'statusHighlights' => $this->buildStatusHighlights(),
        ]);
    }

    private function sanitizeReturnTo(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        if (str_contains($value, "\n") || str_contains($value, "\r") || strlen($value) > 2000) {
            return null;
        }

        return $value;
    }

    private function loadAssetModel(): void
    {
        $this->assetModel = Asset::query()
            ->with(['location', 'currentEmployee', 'contract.supplier', 'warrantySupplier'])
            ->withCount(['movements', 'notes', 'attachments'])
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);
    }

    /**
     * @return array<int, array{
     *     label:string,
     *     value:string,
     *     description:string,
     *     href:?string,
     *     badge:?array{label:string, tone:string}
     * }>
     */
    private function buildOverviewCards(): array
    {
        $asset = $this->assetModel;
        $product = $this->productModel;

        if (! $asset || ! $product) {
            return [];
        }

        $holder = $this->buildHolderSummary();
        $contract = $this->buildContractSummary();

        return [
            [
                'label' => 'Producto',
                'value' => $product->name,
                'description' => collect([
                    $product->category?->name,
                    $product->brand?->name,
                ])->filter()->implode(' · ') ?: 'Sin categoría o marca visible.',
                'href' => route('inventory.products.show', ['product' => $product->id]),
                'badge' => null,
            ],
            [
                'label' => 'Ubicación',
                'value' => data_get($asset, 'location.name') ?? 'Sin ubicación',
                'description' => 'Activo ID '.$asset->id,
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Tenencia actual',
                'value' => $holder['value'],
                'description' => $holder['description'],
                'href' => $holder['href'],
                'badge' => $holder['badge'],
            ],
            [
                'label' => 'Contrato',
                'value' => $contract['value'],
                'description' => $contract['description'],
                'href' => $contract['href'],
                'badge' => $contract['badge'],
            ],
        ];
    }

    /**
     * @return array<int, array{
     *     label:string,
     *     value:string,
     *     description:string,
     *     href:?string,
     *     badge:?array{label:string, tone:string}
     * }>
     */
    private function buildLifecycleCards(): array
    {
        $asset = $this->assetModel;

        if (! $asset) {
            return [];
        }

        $replacement = $this->buildReplacementSummary();
        $warranty = $this->buildWarrantySummary();

        return [
            [
                'label' => 'Costo de adquisición',
                'value' => $this->formatAcquisitionCost(),
                'description' => 'Monto capturado para referencia financiera del activo.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Vida útil (meses)',
                'value' => $this->resolveUsefulLifeLabel(),
                'description' => 'Meses considerados para estimar renovación o reemplazo.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Fecha estimada de reemplazo',
                'value' => $replacement['value'],
                'description' => $replacement['description'],
                'href' => null,
                'badge' => $replacement['badge'],
            ],
            [
                'label' => 'Garantía',
                'value' => $warranty['value'],
                'description' => $warranty['description'],
                'href' => null,
                'badge' => $warranty['badge'],
            ],
        ];
    }

    /**
     * @return array{movements:int, notes:int, attachments:?int}
     */
    private function buildHeaderCounts(): array
    {
        $asset = $this->assetModel;

        if (! $asset) {
            return [
                'movements' => 0,
                'notes' => 0,
                'attachments' => Gate::allows('attachments.view') ? 0 : null,
            ];
        }

        return [
            'movements' => (int) $asset->movements_count,
            'notes' => (int) $asset->notes_count,
            'attachments' => Gate::allows('attachments.view')
                ? (int) $asset->attachments_count
                : null,
        ];
    }

    /**
     * @return array<int, array{label:string, tone:string}>
     */
    private function buildStatusHighlights(): array
    {
        $highlights = [];
        $replacement = $this->buildReplacementSummary();
        $warranty = $this->buildWarrantySummary();

        if ($replacement['badge'] !== null && $replacement['badge']['tone'] !== 'neutral') {
            $highlights[] = $replacement['badge'];
        }

        if ($warranty['badge'] !== null && $warranty['badge']['tone'] !== 'neutral') {
            $highlights[] = $warranty['badge'];
        }

        return $highlights;
    }

    /**
     * @return array{value:string, description:string, href:?string, badge:?array{label:string, tone:string}}
     */
    private function buildHolderSummary(): array
    {
        $asset = $this->assetModel;

        if (! $asset) {
            return [
                'value' => 'Sin datos',
                'description' => 'No se pudo resolver la tenencia actual.',
                'href' => null,
                'badge' => null,
            ];
        }

        if ($asset->status === Asset::STATUS_AVAILABLE) {
            return [
                'value' => 'Disponible para operar',
                'description' => 'El activo está disponible y no tiene resguardo activo.',
                'href' => null,
                'badge' => ['label' => $asset->status, 'tone' => 'success'],
            ];
        }

        if ($asset->status === Asset::STATUS_PENDING_RETIREMENT) {
            return [
                'value' => 'Pendiente de retiro',
                'description' => 'Requiere cierre operativo antes de salir del inventario activo.',
                'href' => null,
                'badge' => ['label' => $asset->status, 'tone' => 'warning'],
            ];
        }

        if ($asset->status === Asset::STATUS_RETIRED) {
            return [
                'value' => 'Fuera de operación',
                'description' => 'Activo retirado del inventario operativo.',
                'href' => null,
                'badge' => ['label' => $asset->status, 'tone' => 'danger'],
            ];
        }

        if ($asset->currentEmployee) {
            $details = [$asset->currentEmployee->name];

            if ($asset->status === Asset::STATUS_LOANED && $asset->loan_due_date) {
                $details[] = 'Vence: '.$asset->loan_due_date->format('d/m/Y');
            }

            return [
                'value' => $asset->currentEmployee->rpe,
                'description' => implode(' · ', $details),
                'href' => route('employees.show', ['employee' => $asset->currentEmployee->id]),
                'badge' => [
                    'label' => $asset->status,
                    'tone' => $asset->status === Asset::STATUS_LOANED ? 'warning' : 'info',
                ],
            ];
        }

        return [
            'value' => 'Sin responsable visible',
            'description' => collect([
                'Sin tenencia registrada (estado legacy o ajuste manual).',
                $asset->status === Asset::STATUS_LOANED && $asset->loan_due_date
                    ? 'Vence: '.$asset->loan_due_date->format('d/m/Y')
                    : null,
            ])->filter()->implode(' · '),
            'href' => null,
            'badge' => [
                'label' => $asset->status,
                'tone' => 'warning',
            ],
        ];
    }

    /**
     * @return array{value:string, description:string, href:?string, badge:?array{label:string, tone:string}}
     */
    private function buildContractSummary(): array
    {
        $contract = $this->assetModel?->contract;

        if (! $contract) {
            return [
                'value' => 'Sin contrato vinculado',
                'description' => 'No hay cobertura contractual asociada.',
                'href' => null,
                'badge' => null,
            ];
        }

        $validity = collect([
            $contract->start_date?->format('d/m/Y'),
            $contract->end_date?->format('d/m/Y'),
        ])->filter()->implode(' al ');

        $description = collect([
            $contract->supplier?->name,
            $validity !== '' ? $validity : null,
        ])->filter()->implode(' · ');

        return [
            'value' => $contract->identifier,
            'description' => $description !== '' ? $description : 'Contrato sin proveedor o vigencia definida.',
            'href' => route('inventory.contracts.show', ['contract' => $contract->id]),
            'badge' => [
                'label' => $contract->type_label,
                'tone' => $contract->type === 'lease' ? 'warning' : 'info',
            ],
        ];
    }

    /**
     * @return array{value:string, description:string, badge:?array{label:string, tone:string}}
     */
    private function buildReplacementSummary(): array
    {
        $replacementDate = $this->assetModel?->expected_replacement_date;

        if (! $replacementDate) {
            return [
                'value' => 'Sin fecha estimada',
                'description' => 'Captura vida útil o fecha manual para activar seguimiento.',
                'badge' => ['label' => 'Sin seguimiento', 'tone' => 'neutral'],
            ];
        }

        $today = Carbon::today();
        $windowDays = $this->getSettingsStore()->getInt('gatic.alerts.renewals.due_soon_window_days_default', 90);
        $allowed = $this->getSettingsStore()->getIntList('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180]);

        if ($allowed === []) {
            $allowed = [30, 60, 90, 180];
        }

        sort($allowed);

        if (! in_array($windowDays, $allowed, true)) {
            $windowDays = $allowed[0];
        }

        $isOverdue = $replacementDate->lt($today);
        $isDueSoon = ! $isOverdue && $replacementDate->lte($today->copy()->addDays($windowDays));

        $badge = ['label' => 'En tiempo', 'tone' => 'success'];
        $description = 'Seguimiento dentro de la ventana operativa actual.';

        if ($isOverdue) {
            $badge = ['label' => 'Vencido', 'tone' => 'danger'];
            $description = 'La fecha estimada ya pasó y requiere atención.';
        } elseif ($isDueSoon) {
            $badge = ['label' => 'Por vencer', 'tone' => 'warning'];
            $description = "Dentro de la ventana de {$windowDays} días configurada para renovaciones.";
        }

        return [
            'value' => $replacementDate->format('d/m/Y'),
            'description' => $description,
            'badge' => $badge,
        ];
    }

    /**
     * @return array{value:string, description:string, badge:?array{label:string, tone:string}}
     */
    private function buildWarrantySummary(): array
    {
        $asset = $this->assetModel;

        if (! $asset) {
            return [
                'value' => 'Sin garantía',
                'description' => 'No se pudo resolver la cobertura.',
                'badge' => null,
            ];
        }

        $hasWarranty = $asset->warranty_start_date
            || $asset->warranty_end_date
            || $asset->warranty_supplier_id
            || $asset->warranty_notes;

        if (! $hasWarranty) {
            return [
                'value' => 'Sin garantía registrada',
                'description' => 'No existe proveedor, vigencia o nota de garantía.',
                'badge' => ['label' => 'Sin cobertura', 'tone' => 'neutral'],
            ];
        }

        $period = collect([
            $asset->warranty_start_date?->format('d/m/Y'),
            $asset->warranty_end_date?->format('d/m/Y'),
        ])->filter()->implode(' al ');

        $description = collect([
            $asset->warrantySupplier?->name,
            $period !== '' ? $period : null,
            $asset->warranty_notes ? trim($asset->warranty_notes) : null,
        ])->filter()->implode(' · ');

        $badge = ['label' => 'Cobertura registrada', 'tone' => 'success'];

        if ($asset->warranty_end_date) {
            $today = Carbon::today();
            $dueSoonDays = $this->getSettingsStore()->getInt('gatic.alerts.warranties.due_soon_window_days_default', 30);
            $isExpired = $asset->warranty_end_date->lt($today);
            $isDueSoon = ! $isExpired && $asset->warranty_end_date->lte($today->copy()->addDays(max($dueSoonDays, 1)));

            if ($isExpired) {
                $badge = ['label' => 'Garantía vencida', 'tone' => 'danger'];
            } elseif ($isDueSoon) {
                $badge = ['label' => 'Garantía por vencer', 'tone' => 'warning'];
            }
        }

        return [
            'value' => $asset->warranty_end_date
                ? $asset->warranty_end_date->format('d/m/Y')
                : (data_get($asset, 'warrantySupplier.name') ?? 'Cobertura registrada'),
            'description' => $description !== '' ? $description : 'Existe información de garantía para este activo.',
            'badge' => $badge,
        ];
    }

    private function formatAcquisitionCost(): string
    {
        $asset = $this->assetModel;

        if (! $asset || $asset->acquisition_cost === null) {
            return '—';
        }

        $defaultCurrency = $this->getSettingsStore()->getString('gatic.inventory.money.default_currency', 'MXN');
        $currency = is_string($asset->acquisition_currency) && $asset->acquisition_currency !== ''
            ? $asset->acquisition_currency
            : ($defaultCurrency !== '' ? $defaultCurrency : 'MXN');

        return number_format((float) $asset->acquisition_cost, 2).' '.$currency;
    }

    private function resolveUsefulLifeLabel(): string
    {
        $usefulLifeMonths = $this->assetModel?->useful_life_months;

        if ($usefulLifeMonths === null) {
            $usefulLifeMonths = $this->productModel?->category?->default_useful_life_months;
        }

        return $usefulLifeMonths !== null
            ? $usefulLifeMonths.' meses'
            : 'Sin vida útil definida';
    }

    private function getSettingsStore(): SettingsStore
    {
        return app(SettingsStore::class);
    }
}
