<?php

namespace App\Livewire\Inventory\Products;

use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class ProductKardex extends Component
{
    use WithPagination;

    private const PAGE_NAME = 'kardex_page';

    private const PER_PAGE = 15;

    protected string $paginationTheme = 'bootstrap';

    public int $productId;

    public ?string $errorId = null;

    public int $movementCount = 0;

    public int $adjustmentCount = 0;

    public function mount(string $product): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $product = $this->loadProductOrAbort();
        $this->movementCount = ProductQuantityMovement::query()
            ->where('product_id', $this->productId)
            ->count();
        $this->adjustmentCount = InventoryAdjustmentEntry::query()
            ->where('subject_type', Product::class)
            ->where('subject_id', $this->productId)
            ->count();

        try {
            $kardexEntries = $this->getKardexEntries();
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $this->errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrió un error al cargar el kardex.',
                errorId: $this->errorId,
            );

            $kardexEntries = $this->emptyPaginator(self::PER_PAGE);
        }

        return view('livewire.inventory.products.product-kardex', [
            'product' => $product,
            'entries' => $kardexEntries,
            'headerSubtitle' => $this->buildHeaderSubtitle($product),
            'summaryCards' => $this->buildSummaryCards($product),
            'statusHighlights' => $this->buildStatusHighlights($product),
        ]);
    }

    /**
     * Build kardex entries from movements and adjustments, ordered chronologically.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function getKardexEntries(): LengthAwarePaginator
    {
        $page = DB::query()
            ->fromSub($this->buildKardexEntriesQuery(), 'kardex_entries')
            ->orderByDesc('entry_date')
            ->orderByDesc('source_priority')
            ->orderByDesc('source_id')
            ->paginate(self::PER_PAGE, ['*'], self::PAGE_NAME);

        return $page->through(fn (object $entry): array => $this->normalizeEntry($entry));
    }

    /**
     * @phpstan-return QueryBuilder
     */
    private function buildKardexEntriesQuery(): QueryBuilder
    {
        $movements = DB::table('product_quantity_movements')
            ->leftJoin('users as actor_users', 'actor_users.id', '=', 'product_quantity_movements.actor_user_id')
            ->leftJoin('employees', 'employees.id', '=', 'product_quantity_movements.employee_id')
            ->where('product_quantity_movements.product_id', $this->productId)
            ->selectRaw('? as source_type', ['movement'])
            ->selectRaw('product_quantity_movements.id as source_id')
            ->selectRaw('product_quantity_movements.created_at as entry_date')
            ->selectRaw('2 as source_priority')
            ->selectRaw('product_quantity_movements.direction as movement_direction')
            ->selectRaw('product_quantity_movements.qty as qty')
            ->selectRaw('product_quantity_movements.qty_before as qty_before')
            ->selectRaw('product_quantity_movements.qty_after as qty_after')
            ->selectRaw('coalesce(actor_users.name, ?) as actor_name', ['-'])
            ->selectRaw('employees.name as employee_name')
            ->selectRaw('product_quantity_movements.note as note')
            ->selectRaw('null as before_payload')
            ->selectRaw('null as after_payload');

        return DB::table('inventory_adjustment_entries')
            ->leftJoin('inventory_adjustments', 'inventory_adjustments.id', '=', 'inventory_adjustment_entries.inventory_adjustment_id')
            ->leftJoin('users as adjustment_actors', 'adjustment_actors.id', '=', 'inventory_adjustments.actor_user_id')
            ->where('inventory_adjustment_entries.subject_type', Product::class)
            ->where('inventory_adjustment_entries.subject_id', $this->productId)
            ->selectRaw('? as source_type', ['adjustment'])
            ->selectRaw('inventory_adjustment_entries.id as source_id')
            ->selectRaw('inventory_adjustment_entries.created_at as entry_date')
            ->selectRaw('1 as source_priority')
            ->selectRaw('null as movement_direction')
            ->selectRaw('null as qty')
            ->selectRaw('null as qty_before')
            ->selectRaw('null as qty_after')
            ->selectRaw('coalesce(adjustment_actors.name, ?) as actor_name', ['-'])
            ->selectRaw('null as employee_name')
            ->selectRaw('inventory_adjustments.reason as note')
            ->selectRaw('inventory_adjustment_entries.before as before_payload')
            ->selectRaw('inventory_adjustment_entries.after as after_payload')
            ->unionAll($movements);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeEntry(object $entry): array
    {
        if ($entry->source_type === 'adjustment') {
            return $this->normalizeAdjustmentEntry($entry);
        }

        return $this->normalizeMovementEntry($entry);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMovementEntry(object $entry): array
    {
        $isOut = $entry->movement_direction === ProductQuantityMovement::DIRECTION_OUT;

        return [
            'type' => $isOut ? 'out' : 'in',
            'type_label' => $isOut ? 'Salida' : 'Entrada',
            'qty' => (int) ($entry->qty ?? 0),
            'date' => $this->normalizeEntryDate($entry->entry_date ?? null),
            'actor_name' => is_string($entry->actor_name ?? null) && $entry->actor_name !== '' ? $entry->actor_name : '-',
            'employee_name' => is_string($entry->employee_name ?? null) && $entry->employee_name !== '' ? $entry->employee_name : null,
            'note' => is_string($entry->note ?? null) && $entry->note !== '' ? $entry->note : null,
            'qty_before' => (int) ($entry->qty_before ?? 0),
            'qty_after' => (int) ($entry->qty_after ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAdjustmentEntry(object $entry): array
    {
        $before = $this->extractQtyTotal($entry->before_payload ?? null);
        $after = $this->extractQtyTotal($entry->after_payload ?? null);

        return [
            'type' => 'adjustment',
            'type_label' => 'Ajuste',
            'qty' => abs($after - $before),
            'date' => $this->normalizeEntryDate($entry->entry_date ?? null),
            'actor_name' => is_string($entry->actor_name ?? null) && $entry->actor_name !== '' ? $entry->actor_name : '-',
            'employee_name' => null,
            'note' => is_string($entry->note ?? null) && $entry->note !== '' ? $entry->note : null,
            'qty_before' => $before,
            'qty_after' => $after,
        ];
    }

    private function normalizeEntryDate(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return Carbon::now();
    }

    private function extractQtyTotal(mixed $payload): int
    {
        if (is_array($payload)) {
            return (int) ($payload['qty_total'] ?? 0);
        }

        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);

            if (is_array($decoded)) {
                return (int) ($decoded['qty_total'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        $query = request()->query();
        unset($query[self::PAGE_NAME]);

        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            1,
            [
                'path' => request()->url(),
                'query' => $query,
                'pageName' => self::PAGE_NAME,
            ]
        );
    }

    private function loadProductOrAbort(): Product
    {
        /** @var Product $product */
        $product = Product::query()
            ->with(['category', 'brand'])
            ->findOrFail($this->productId);

        // Only quantity products (non-serialized) can have kardex
        if ($product->category?->is_serialized) {
            abort(404);
        }

        return $product;
    }

    private function buildHeaderSubtitle(Product $product): string
    {
        return collect([
            $product->category?->name,
            $product->brand?->name,
            'Trazabilidad por cantidad',
        ])->filter()->implode(' · ');
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
    private function buildSummaryCards(Product $product): array
    {
        $totalRecords = $this->movementCount + $this->adjustmentCount;
        $isLowStock = $product->low_stock_threshold !== null
            && $product->qty_total !== null
            && $product->qty_total <= $product->low_stock_threshold;

        return [
            [
                'label' => 'Stock actual',
                'value' => (string) ((int) ($product->qty_total ?? 0)),
                'description' => 'Saldo visible del producto por cantidad al momento de abrir el kardex.',
                'href' => Gate::allows('inventory.manage')
                    ? route('inventory.products.movements.quantity', ['product' => $product->id])
                    : null,
                'badge' => $isLowStock
                    ? ['label' => 'Stock bajo', 'tone' => 'warning']
                    : ['label' => 'Operativo', 'tone' => 'success'],
            ],
            [
                'label' => 'Registros totales',
                'value' => (string) $totalRecords,
                'description' => 'Suma movimientos y ajustes incluidos en la cronología.',
                'href' => null,
                'badge' => $totalRecords > 0
                    ? ['label' => 'Con historial', 'tone' => 'info']
                    : null,
            ],
            [
                'label' => 'Movimientos',
                'value' => (string) $this->movementCount,
                'description' => 'Entradas y salidas capturadas por operación diaria.',
                'href' => null,
                'badge' => null,
            ],
            [
                'label' => 'Ajustes',
                'value' => (string) $this->adjustmentCount,
                'description' => 'Correcciones administrativas aplicadas al inventario.',
                'href' => Gate::allows('admin-only')
                    ? route('inventory.products.adjust', ['product' => $product->id])
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
    private function buildStatusHighlights(Product $product): array
    {
        $highlights = [
            [
                'label' => 'Por cantidad',
                'tone' => 'warning',
            ],
        ];

        if (is_string($product->brand?->name) && $product->brand->name !== '') {
            $highlights[] = [
                'label' => $product->brand->name,
                'tone' => 'neutral',
            ];
        }

        if ($product->low_stock_threshold !== null && $product->qty_total !== null && $product->qty_total <= $product->low_stock_threshold) {
            $highlights[] = [
                'label' => 'Stock bajo',
                'tone' => 'warning',
            ];
        }

        return $highlights;
    }
}
