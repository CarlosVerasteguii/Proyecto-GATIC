<?php

namespace App\Livewire\Inventory\Products;

use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class ProductKardex extends Component
{
    private const PAGE_NAME = 'kardex_page';

    public int $productId;

    public ?string $errorId = null;

    public function mount(string $product): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->loadProductOrAbort();
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $product = $this->loadProductOrAbort();

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

            $kardexEntries = $this->emptyPaginator(15);
        }

        return view('livewire.inventory.products.product-kardex', [
            'product' => $product,
            'entries' => $kardexEntries,
        ]);
    }

    /**
     * Build kardex entries from movements and adjustments, ordered chronologically.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function getKardexEntries(): LengthAwarePaginator
    {
        // Get quantity movements
        $movements = ProductQuantityMovement::query()
            ->where('product_id', $this->productId)
            ->with(['actorUser', 'employee'])
            ->get()
            ->map(fn (ProductQuantityMovement $m): array => $this->normalizeMovement($m))
            ->toBase();

        // Get inventory adjustment entries for this product
        $adjustments = InventoryAdjustmentEntry::query()
            ->where('subject_type', Product::class)
            ->where('subject_id', $this->productId)
            ->with(['adjustment.actor'])
            ->get()
            ->map(fn (InventoryAdjustmentEntry $e): array => $this->normalizeAdjustment($e))
            ->toBase();

        // Combine and sort by date descending (most recent first)
        /** @var Collection<int, array<string, mixed>> $combined */
        $combined = $movements->merge($adjustments)
            ->sortByDesc(static function (array $entry): int {
                $date = $entry['date'] ?? null;

                return $date instanceof \DateTimeInterface ? $date->getTimestamp() : 0;
            })
            ->values();

        return $this->paginateCollection($combined, 15);
    }

    /**
     * Normalize a quantity movement to a common format.
     *
     * @return array<string, mixed>
     */
    private function normalizeMovement(ProductQuantityMovement $movement): array
    {
        $isOut = $movement->direction === ProductQuantityMovement::DIRECTION_OUT;

        return [
            'type' => $isOut ? 'out' : 'in',
            'type_label' => $isOut ? 'Salida' : 'Entrada',
            'qty' => $movement->qty,
            'date' => $movement->created_at ?? Carbon::now(),
            'actor_name' => $movement->actorUser?->name ?? '-',
            'employee_name' => $movement->employee?->name,
            'note' => $movement->note,
            'qty_before' => $movement->qty_before,
            'qty_after' => $movement->qty_after,
        ];
    }

    /**
     * Normalize an adjustment entry to a common format.
     *
     * @return array<string, mixed>
     */
    private function normalizeAdjustment(InventoryAdjustmentEntry $entry): array
    {
        $before = $entry->before['qty_total'] ?? 0;
        $after = $entry->after['qty_total'] ?? 0;
        $delta = (int) $after - (int) $before;

        return [
            'type' => 'adjustment',
            'type_label' => 'Ajuste',
            'qty' => abs($delta),
            'date' => $entry->created_at ?? Carbon::now(),
            'actor_name' => $entry->adjustment?->actor?->name ?? '-',
            'employee_name' => null,
            'note' => $entry->adjustment?->reason,
            'qty_before' => (int) $before,
            'qty_after' => (int) $after,
        ];
    }

    /**
     * Paginate a collection manually.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = request()->input(self::PAGE_NAME, 1);
        $offset = ((int) $page - 1) * $perPage;

        $query = request()->query();
        unset($query[self::PAGE_NAME]);

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            (int) $page,
            [
                'path' => request()->url(),
                'query' => $query,
                'pageName' => self::PAGE_NAME,
            ]
        );
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
}
