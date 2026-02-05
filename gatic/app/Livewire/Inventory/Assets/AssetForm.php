<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
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

    public ?string $warrantyStartDate = null;

    public ?string $warrantyEndDate = null;

    public ?int $warrantySupplierId = null;

    public ?string $warrantyNotes = null;

    public ?string $acquisitionCost = null;

    public ?string $acquisitionCurrency = null;

    /**
     * @var list<string>
     */
    public array $allowedCurrencies = [];

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $locations = [];

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $suppliers = [];

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

        $this->suppliers = Supplier::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ])
            ->all();

        /** @var mixed $currencies */
        $currencies = config('gatic.inventory.money.allowed_currencies', ['MXN', 'USD']);
        $currencyList = is_array($currencies) ? $currencies : ['MXN', 'USD'];

        /** @var list<string> $normalizedCurrencies */
        $normalizedCurrencies = array_values(array_filter(
            array_map(
                static fn (mixed $value): ?string => is_string($value) ? strtoupper(trim($value)) : null,
                $currencyList,
            ),
            static fn (?string $value): bool => is_string($value) && (bool) preg_match('/^[A-Z]{3}$/', $value),
        ));

        $this->allowedCurrencies = $normalizedCurrencies !== [] ? $normalizedCurrencies : ['MXN', 'USD'];

        /** @var mixed $defaultCurrency */
        $defaultCurrency = config('gatic.inventory.money.default_currency', 'MXN');
        $normalizedDefaultCurrency = is_string($defaultCurrency) ? strtoupper(trim($defaultCurrency)) : 'MXN';
        if (! in_array($normalizedDefaultCurrency, $this->allowedCurrencies, true)) {
            $normalizedDefaultCurrency = $this->allowedCurrencies[0] ?? 'MXN';
        }

        $this->acquisitionCurrency = $normalizedDefaultCurrency;

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
        $this->warrantyStartDate = $model->warranty_start_date?->format('Y-m-d');
        $this->warrantyEndDate = $model->warranty_end_date?->format('Y-m-d');
        $this->warrantySupplierId = $model->warranty_supplier_id;
        $this->warrantyNotes = $model->warranty_notes;
        $this->acquisitionCost = $model->acquisition_cost;
        $this->acquisitionCurrency = $model->acquisition_currency ?? $this->acquisitionCurrency;
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

        $requiresEmployee = $this->requiresCurrentEmployeeSelection();

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
                $requiresEmployee ? 'required' : 'nullable',
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
            'warrantyStartDate' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],
            'warrantyEndDate' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                $this->warrantyStartDate !== null && $this->warrantyEndDate !== null
                    ? 'after_or_equal:warrantyStartDate'
                    : null,
            ],
            'warrantySupplierId' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            'warrantyNotes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'acquisitionCost' => [
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'acquisitionCurrency' => [
                'nullable',
                'required_with:acquisitionCost',
                'string',
                'size:3',
                Rule::in($this->allowedCurrencies),
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
            'current_employee_id.required' => 'El empleado es obligatorio cuando el estado es Asignado o Prestado.',
            'current_employee_id.exists' => 'El empleado seleccionado no es válido.',
            'warrantyStartDate.date' => 'La fecha de inicio de garantía no es válida.',
            'warrantyStartDate.date_format' => 'La fecha de inicio de garantía debe tener el formato AAAA-MM-DD.',
            'warrantyEndDate.date' => 'La fecha de fin de garantía no es válida.',
            'warrantyEndDate.date_format' => 'La fecha de fin de garantía debe tener el formato AAAA-MM-DD.',
            'warrantyEndDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'warrantySupplierId.exists' => 'El proveedor seleccionado no es válido.',
            'warrantyNotes.max' => 'Las notas de garantía no deben exceder 5000 caracteres.',
            'acquisitionCost.numeric' => 'El costo de adquisición debe ser un número.',
            'acquisitionCost.min' => 'El costo de adquisición debe ser mayor o igual a 0.',
            'acquisitionCost.regex' => 'El costo de adquisición debe tener máximo 2 decimales.',
            'acquisitionCurrency.size' => 'La moneda debe ser un código de 3 caracteres.',
            'acquisitionCurrency.in' => 'La moneda seleccionada no es válida.',
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

        $requiresEmployee = in_array($validated['status'], [Asset::STATUS_ASSIGNED, Asset::STATUS_LOANED], true);
        $currentEmployeeId = $requiresEmployee ? ($validated['current_employee_id'] ?? null) : null;

        $acquisitionCost = isset($validated['acquisitionCost']) && $validated['acquisitionCost'] !== ''
            ? (string) $validated['acquisitionCost']
            : null;
        /** @var mixed $defaultCurrency */
        $defaultCurrency = config('gatic.inventory.money.default_currency', 'MXN');
        $normalizedDefaultCurrency = is_string($defaultCurrency) ? strtoupper(trim($defaultCurrency)) : ($this->allowedCurrencies[0] ?? 'MXN');
        if (! in_array($normalizedDefaultCurrency, $this->allowedCurrencies, true)) {
            $normalizedDefaultCurrency = $this->allowedCurrencies[0] ?? 'MXN';
        }

        $acquisitionCurrency = $acquisitionCost !== null
            ? ($validated['acquisitionCurrency'] ?? $normalizedDefaultCurrency)
            : null;

        if ($this->assetId === null) {
            Asset::query()->create([
                'product_id' => $this->productId,
                'serial' => $validated['serial'],
                'asset_tag' => $validated['asset_tag'],
                'location_id' => $validated['location_id'],
                'status' => $validated['status'],
                'current_employee_id' => $currentEmployeeId,
                'warranty_start_date' => $validated['warrantyStartDate'] ?? null,
                'warranty_end_date' => $validated['warrantyEndDate'] ?? null,
                'warranty_supplier_id' => $validated['warrantySupplierId'] ?? null,
                'warranty_notes' => $validated['warrantyNotes'] ?? null,
                'acquisition_cost' => $acquisitionCost,
                'acquisition_currency' => $acquisitionCurrency,
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
        $model->current_employee_id = $currentEmployeeId;
        $model->warranty_start_date = $validated['warrantyStartDate'] ?? null;
        $model->warranty_end_date = $validated['warrantyEndDate'] ?? null;
        $model->warranty_supplier_id = $validated['warrantySupplierId'] ?? null;
        $model->warranty_notes = $validated['warrantyNotes'] ?? null;
        $model->acquisition_cost = $acquisitionCost;
        $model->acquisition_currency = $acquisitionCurrency;
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
            'suppliers' => $this->suppliers,
            'requiresEmployeeSelection' => $this->requiresCurrentEmployeeSelection(),
            'allowedCurrencies' => $this->allowedCurrencies,
        ]);
    }

    private function requiresCurrentEmployeeSelection(): bool
    {
        return in_array($this->status, [Asset::STATUS_ASSIGNED, Asset::STATUS_LOANED], true);
    }

    public function updatedStatus(): void
    {
        if ($this->requiresCurrentEmployeeSelection()) {
            return;
        }

        $this->current_employee_id = null;
        $this->resetErrorBag('current_employee_id');
    }
}
