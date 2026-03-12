<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductShow extends Component
{
    public int $productId;

    public ?Product $productModel = null;

    public bool $productIsSerialized = false;

    public int $total = 0;

    public int $available = 0;

    public int $unavailable = 0;

    public int $movementCount = 0;

    public int $adjustmentCount = 0;

    public int $retiredCount = 0;

    /**
     * @var array<int, array{status:string, count:int}>
     */
    public array $statusBreakdown = [];

    public function mount(string $product): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $this->loadProduct();
    }

    #[On('inventory:product-changed')]
    public function onProductChanged(int $productId): void
    {
        if ($productId !== $this->productId) {
            return;
        }

        $this->loadProduct();
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.products.product-show', [
            'product' => $this->productModel,
            'productIsSerialized' => $this->productIsSerialized,
            'headerSubtitle' => $this->buildHeaderSubtitle(),
            'headerMetrics' => $this->buildHeaderMetrics(),
            'overviewCards' => $this->buildOverviewCards(),
            'operationalCards' => $this->buildOperationalCards(),
            'statusHighlights' => $this->buildStatusHighlights(),
        ]);
    }

    private function loadProduct(): void
    {
        $this->productModel = Product::query()
            ->with(['category', 'brand', 'supplier'])
            ->withCount(['notes', 'attachments'])
            ->findOrFail($this->productId);

        $this->productIsSerialized = (bool) $this->productModel->category?->is_serialized;
        $this->movementCount = 0;
        $this->adjustmentCount = 0;
        $this->retiredCount = 0;

        if (! $this->productIsSerialized) {
            $this->total = (int) ($this->productModel->qty_total ?? 0);
            $this->unavailable = 0;
            $this->available = $this->total;
            $this->statusBreakdown = [];
            $this->movementCount = ProductQuantityMovement::query()
                ->where('product_id', $this->productId)
                ->count();
            $this->adjustmentCount = InventoryAdjustmentEntry::query()
                ->where('subject_type', Product::class)
                ->where('subject_id', $this->productId)
                ->count();

            return;
        }

        $breakdown = Asset::query()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->where('product_id', $this->productId)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $breakdownCounts = [];
        foreach ($breakdown as $status => $count) {
            $breakdownCounts[(string) $status] = (int) $count;
        }

        $retiredCount = $breakdownCounts[Asset::STATUS_RETIRED] ?? 0;
        $totalIncludingRetired = array_sum($breakdownCounts);
        $this->retiredCount = $retiredCount;

        $this->total = max($totalIncludingRetired - $retiredCount, 0);

        $this->unavailable = 0;
        foreach (Asset::UNAVAILABLE_STATUSES as $status) {
            $this->unavailable += (int) ($breakdownCounts[$status] ?? 0);
        }

        $this->available = max($this->total - $this->unavailable, 0);

        $this->statusBreakdown = [];
        foreach (Asset::STATUSES as $status) {
            $this->statusBreakdown[] = [
                'status' => $status,
                'count' => (int) ($breakdownCounts[$status] ?? 0),
            ];
        }
    }

    private function buildHeaderSubtitle(): string
    {
        if (! $this->productModel) {
            return '';
        }

        return collect([
            $this->productModel->category?->name,
            $this->productModel->brand?->name,
            $this->productIsSerialized ? 'Inventario serializado' : 'Inventario por cantidad',
        ])->filter()->implode(' · ');
    }

    /**
     * @return array<int, array{label:string, value:int, variant:?string}>
     */
    private function buildHeaderMetrics(): array
    {
        return [
            [
                'label' => 'Total',
                'value' => $this->total,
                'variant' => null,
            ],
            [
                'label' => 'Disponibles',
                'value' => $this->available,
                'variant' => 'success',
            ],
            [
                'label' => 'No disponibles',
                'value' => $this->unavailable,
                'variant' => 'warning',
            ],
            [
                'label' => $this->productIsSerialized ? 'Activos' : 'Registros',
                'value' => $this->productIsSerialized
                    ? $this->total + $this->retiredCount
                    : ($this->movementCount + $this->adjustmentCount),
                'variant' => 'info',
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
    private function buildOverviewCards(): array
    {
        if (! $this->productModel) {
            return [];
        }

        return [
            [
                'label' => 'Tipo de inventario',
                'value' => $this->productIsSerialized ? 'Serializado' : 'Por cantidad',
                'description' => $this->productIsSerialized
                    ? 'Opera por activo individual y desglosa disponibilidad por estado.'
                    : 'Opera con stock agregado y conserva trazabilidad en kardex.',
                'href' => null,
                'badge' => [
                    'label' => $this->productIsSerialized ? 'Activos' : 'Kardex',
                    'tone' => $this->productIsSerialized ? 'info' : 'warning',
                ],
            ],
            [
                'label' => 'Categoría',
                'value' => $this->productModel->category->name ?? 'Sin categoría',
                'description' => 'Define las reglas operativas y el modelo de inventario.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Marca',
                'value' => $this->productModel->brand->name ?? 'Sin marca',
                'description' => 'Referencia visible en listados, búsqueda y detalle.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Proveedor',
                'value' => $this->productModel->supplier->name ?? 'Sin proveedor',
                'description' => 'Se muestra para contexto operativo y abastecimiento.',
                'href' => null,
                'badge' => null,
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
    private function buildOperationalCards(): array
    {
        if (! $this->productModel) {
            return [];
        }

        if ($this->productIsSerialized) {
            return [
                [
                    'label' => 'Activos operativos',
                    'value' => (string) $this->total,
                    'description' => 'Excluye retirados y mantiene el flujo hacia la lista de activos.',
                    'href' => route('inventory.products.assets.index', ['product' => $this->productModel->id]),
                    'badge' => null,
                ],
                [
                    'label' => 'Disponibles',
                    'value' => (string) $this->available,
                    'description' => 'Listos para asignar o prestar.',
                    'href' => null,
                    'badge' => $this->available > 0
                        ? ['label' => 'Operativo', 'tone' => 'success']
                        : ['label' => 'Sin disponibles', 'tone' => 'danger'],
                ],
                [
                    'label' => 'No disponibles',
                    'value' => (string) $this->unavailable,
                    'description' => 'Asignados, prestados o pendientes de retiro.',
                    'href' => null,
                    'badge' => $this->unavailable > 0
                        ? ['label' => 'Con movimiento', 'tone' => 'warning']
                        : null,
                ],
                [
                    'label' => 'Retirados',
                    'value' => (string) $this->retiredCount,
                    'description' => 'Se mantienen solo como referencia histórica del producto.',
                    'href' => null,
                    'badge' => $this->retiredCount > 0
                        ? ['label' => 'Histórico', 'tone' => 'neutral']
                        : null,
                ],
            ];
        }

        $recordsCount = $this->movementCount + $this->adjustmentCount;
        $thresholdLabel = $this->productModel->low_stock_threshold !== null
            ? (string) $this->productModel->low_stock_threshold
            : 'No configurado';

        return [
            [
                'label' => 'Stock actual',
                'value' => (string) $this->available,
                'description' => 'Cantidad visible para registrar salidas o entradas.',
                'href' => Gate::allows('inventory.manage')
                    ? route('inventory.products.movements.quantity', ['product' => $this->productModel->id])
                    : null,
                'badge' => $this->isLowStock()
                    ? ['label' => 'Stock bajo', 'tone' => 'warning']
                    : ['label' => 'Operativo', 'tone' => 'success'],
            ],
            [
                'label' => 'Umbral de stock bajo',
                'value' => $thresholdLabel,
                'description' => $this->productModel->low_stock_threshold !== null
                    ? 'Dispara atención visual cuando el stock llega al límite definido.'
                    : 'Configúralo para resaltar productos con reposición pendiente.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Registros en kardex',
                'value' => (string) $recordsCount,
                'description' => 'Entradas, salidas y ajustes reunidos en una sola vista cronológica.',
                'href' => route('inventory.products.kardex', ['product' => $this->productModel->id]),
                'badge' => $recordsCount > 0
                    ? ['label' => 'Con historial', 'tone' => 'info']
                    : null,
            ],
            [
                'label' => 'Ajustes aplicados',
                'value' => (string) $this->adjustmentCount,
                'description' => 'Correcciones administrativas sobre el inventario agregado.',
                'href' => Gate::allows('admin-only')
                    ? route('inventory.products.adjust', ['product' => $this->productModel->id])
                    : null,
                'badge' => $this->adjustmentCount > 0
                    ? ['label' => 'Auditable', 'tone' => 'warning']
                    : null,
            ],
        ];
    }

    /**
     * @return array<int, array{label:string, tone:string}>
     */
    private function buildStatusHighlights(): array
    {
        $highlights = [
            [
                'label' => $this->productIsSerialized ? 'Serializado' : 'Por cantidad',
                'tone' => $this->productIsSerialized ? 'info' : 'warning',
            ],
        ];

        $supplierName = data_get($this->productModel, 'supplier.name');

        if (is_string($supplierName) && $supplierName !== '') {
            $highlights[] = [
                'label' => $supplierName,
                'tone' => 'neutral',
            ];
        }

        if ($this->isLowStock()) {
            $highlights[] = [
                'label' => 'Stock bajo',
                'tone' => 'warning',
            ];
        }

        return $highlights;
    }

    private function isLowStock(): bool
    {
        return ! $this->productIsSerialized
            && $this->productModel->low_stock_threshold !== null
            && $this->productModel->qty_total !== null
            && $this->total <= $this->productModel->low_stock_threshold;
    }
}
