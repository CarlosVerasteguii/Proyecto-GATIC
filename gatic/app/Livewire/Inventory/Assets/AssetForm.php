<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AssetForm extends Component
{
    public int $productId;

    public ?int $assetId = null;

    public ?Product $productModel = null;

    public bool $productIsSerialized = false;

    public bool $requiresAssetTag = false;

    public string $serial = '';

    public ?string $asset_tag = null;

    public ?int $location_id = null;

    public string $status = Asset::STATUS_AVAILABLE;

    public ?int $current_employee_id = null;

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $locations = [];

    /**
     * @var list<string>
     */
    public array $statuses = Asset::STATUSES;

    public function mount(string $product, ?string $asset = null): void
    {
        Gate::authorize('inventory.manage');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        $this->productIsSerialized = (bool) $this->productModel->category?->is_serialized;
        $this->requiresAssetTag = (bool) $this->productModel->category?->requires_asset_tag;

        $this->locations = Location::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
            ])
            ->all();

        if (! $asset) {
            return;
        }

        if (! ctype_digit($asset)) {
            abort(404);
        }

        $this->assetId = (int) $asset;

        $model = Asset::query()
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);

        $this->serial = $model->serial;
        $this->asset_tag = $model->asset_tag;
        $this->location_id = $model->location_id;
        $this->status = $model->status;
        $this->current_employee_id = $model->current_employee_id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $serialRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('assets', 'serial')
                ->where(fn ($query) => $query->where('product_id', $this->productId))
                ->ignore($this->assetId),
        ];

        $assetTagRules = [
            'nullable',
            'string',
            'max:255',
            Rule::unique('assets', 'asset_tag')->ignore($this->assetId),
        ];

        if ($this->requiresAssetTag) {
            $assetTagRules[0] = 'required';
        }

        return [
            'serial' => $serialRules,
            'asset_tag' => $assetTagRules,
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
            ],
            'status' => [
                'required',
                'string',
                Rule::in($this->statuses),
            ],
            'current_employee_id' => [
                Rule::requiredIf($this->status === Asset::STATUS_ASSIGNED),
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'serial.required' => 'El serial es obligatorio.',
            'serial.unique' => 'Ya existe un activo con ese serial para este producto.',
            'asset_tag.required' => 'El asset tag es obligatorio para esta categoría.',
            'asset_tag.unique' => 'Ese asset tag ya está en uso.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no es válida.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'current_employee_id.required' => 'El empleado es obligatorio cuando el estado es Asignado.',
            'current_employee_id.exists' => 'El empleado seleccionado no es válido.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('inventory.manage');

        if (! $this->productIsSerialized) {
            return redirect()
                ->route('inventory.products.index')
                ->with('status', 'No hay activos para productos por cantidad.');
        }

        $this->serial = Asset::normalizeSerial($this->serial) ?? '';
        $this->asset_tag = Asset::normalizeAssetTag($this->asset_tag);

        $validated = $this->validate();

        if ($this->assetId === null) {
            Asset::query()->create([
                'product_id' => $this->productId,
                'serial' => $validated['serial'],
                'asset_tag' => $validated['asset_tag'],
                'location_id' => $validated['location_id'],
                'status' => $validated['status'],
                'current_employee_id' => $validated['current_employee_id'] ?? null,
            ]);

            return redirect()
                ->route('inventory.products.assets.index', ['product' => $this->productId])
                ->with('status', 'Activo creado.');
        }

        $model = Asset::query()
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);

        $model->serial = $validated['serial'];
        $model->asset_tag = $validated['asset_tag'];
        $model->location_id = $validated['location_id'];
        $model->status = $validated['status'];
        $model->save();

        return redirect()
            ->route('inventory.products.assets.index', ['product' => $this->productId])
            ->with('status', 'Activo actualizado.');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.inventory.assets.asset-form', [
            'isEdit' => (bool) $this->assetId,
            'product' => $this->productModel,
            'productIsSerialized' => $this->productIsSerialized,
            'requiresAssetTag' => $this->requiresAssetTag,
            'locations' => $this->locations,
            'statuses' => $this->statuses,
        ]);
    }
}
