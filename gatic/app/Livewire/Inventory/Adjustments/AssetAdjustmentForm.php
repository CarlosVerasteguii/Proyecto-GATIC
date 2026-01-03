<?php

namespace App\Livewire\Inventory\Adjustments;

use App\Actions\Inventory\Adjustments\ApplyAssetAdjustment;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AssetAdjustmentForm extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public ?string $currentStatus = null;

    public ?int $currentLocationId = null;

    public string $newStatus = '';

    public ?int $newLocationId = null;

    public string $reason = '';

    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $locations = [];

    /**
     * @var array{q?: string, page?: string}
     */
    public array $returnQuery = [];

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('admin-only');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->assetModel = Asset::query()
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);

        $this->currentStatus = $this->assetModel->status;
        $this->currentLocationId = $this->assetModel->location_id;

        $this->newStatus = $this->currentStatus;
        $this->newLocationId = $this->currentLocationId;

        $this->locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $l): array => ['id' => $l->id, 'name' => $l->name])
            ->all();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'newStatus' => ['required', 'string', Rule::in(Asset::STATUSES)],
            'newLocationId' => ['required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'newStatus' => 'Nuevo estado',
            'newLocationId' => 'Nueva ubicación',
            'reason' => 'Motivo',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('admin-only');

        $this->validate();

        $action = new ApplyAssetAdjustment;
        $action->execute([
            'asset_id' => $this->assetId,
            'new_status' => $this->newStatus,
            'new_location_id' => $this->newLocationId,
            'reason' => $this->reason,
            'actor_user_id' => auth()->id(),
        ]);

        session()->flash('status', 'Ajuste de activo aplicado.');

        return $this->redirect(
            route('inventory.products.assets.show', ['product' => $this->productId, 'asset' => $this->assetId] + $this->returnQuery),
            navigate: false
        );
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        return view('livewire.inventory.adjustments.asset-adjustment-form', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
            'statuses' => Asset::STATUSES,
        ]);
    }
}
