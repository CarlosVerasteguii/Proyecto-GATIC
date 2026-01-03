<?php

namespace App\Livewire\Inventory\Adjustments;

use App\Actions\Inventory\Adjustments\ApplyProductQuantityAdjustment;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductAdjustmentForm extends Component
{
    public int $productId;

    public ?Product $productModel = null;

    public ?int $currentQty = null;

    public ?int $newQty = null;

    public string $reason = '';

    /**
     * @var array{q?: string, page?: string}
     */
    public array $returnQuery = [];

    public function mount(string $product): void
    {
        Gate::authorize('admin-only');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );

        $this->productId = (int) $product;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if ($this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->currentQty = (int) ($this->productModel->qty_total ?? 0);
        $this->newQty = $this->currentQty;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'newQty' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'newQty' => 'Nueva cantidad',
            'reason' => 'Motivo',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('admin-only');

        $this->validate();

        $action = new ApplyProductQuantityAdjustment;
        $action->execute([
            'product_id' => $this->productId,
            'new_qty' => $this->newQty,
            'reason' => $this->reason,
            'actor_user_id' => auth()->id(),
        ]);

        session()->flash('status', 'Ajuste de inventario aplicado.');

        return $this->redirect(
            route('inventory.products.show', ['product' => $this->productId] + $this->returnQuery),
            navigate: false
        );
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        return view('livewire.inventory.adjustments.product-adjustment-form', [
            'product' => $this->productModel,
        ]);
    }
}
