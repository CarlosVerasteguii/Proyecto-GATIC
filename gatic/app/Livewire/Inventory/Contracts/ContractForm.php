<?php

namespace App\Livewire\Inventory\Contracts;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ContractForm extends Component
{
    use InteractsWithToasts;

    public ?int $contractId = null;

    public string $identifier = '';

    public string $type = '';

    public ?int $supplier_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $notes = '';

    /**
     * Asset IDs linked to this contract.
     *
     * @var list<int>
     */
    public array $linkedAssetIds = [];

    /**
     * Search term for finding assets to link.
     */
    public string $assetSearch = '';

    /**
     * Assets matching the search.
     *
     * @var array<int, array{id:int, serial:string, product_name:string, current_contract_identifier:?string}>
     */
    public array $searchResults = [];

    /**
     * Used to require a second confirmation click when reassigning an asset
     * from a different contract.
     */
    public ?int $pendingReassignAssetId = null;

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $suppliers = [];

    /**
     * @var array<int, array{id:int, serial:string, product_name:string}>
     */
    public array $linkedAssets = [];

    public function mount(?string $contract = null): void
    {
        Gate::authorize('inventory.manage');

        $this->suppliers = Supplier::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ])
            ->all();

        if (! $contract) {
            return;
        }

        if (! ctype_digit($contract)) {
            abort(404);
        }

        $this->contractId = (int) $contract;

        $model = Contract::query()
            ->with(['assets.product' => static fn ($query) => $query->withTrashed()])
            ->findOrFail($this->contractId);

        $this->identifier = $model->identifier;
        $this->type = $model->type;
        $this->supplier_id = $model->supplier_id;
        $this->start_date = $model->start_date?->format('Y-m-d');
        $this->end_date = $model->end_date?->format('Y-m-d');
        $this->notes = $model->notes ?? '';

        $this->linkedAssetIds = $model->assets->pluck('id')->all();
        $this->linkedAssets = $model->assets->map(fn (Asset $asset): array => [
            'id' => $asset->id,
            'serial' => $asset->serial,
            'product_name' => $asset->product->name,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $uniqueIdentifierRule = Rule::unique('contracts', 'identifier');

        if ($this->contractId) {
            $uniqueIdentifierRule = $uniqueIdentifierRule->ignore($this->contractId);
        }

        return [
            'identifier' => [
                'required',
                'string',
                'max:255',
                $uniqueIdentifierRule,
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Contract::TYPES),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'identifier.required' => 'El identificador es obligatorio.',
            'identifier.unique' => 'Ya existe un contrato con este identificador.',
            'type.required' => 'El tipo de contrato es obligatorio.',
            'type.in' => 'El tipo de contrato debe ser "compra" o "arrendamiento".',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'start_date.date' => 'La fecha de inicio no es válida.',
            'end_date.date' => 'La fecha de fin no es válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    public function searchAssets(): void
    {
        Gate::authorize('inventory.manage');

        $this->pendingReassignAssetId = null;
        $this->searchResults = [];

        $term = trim($this->assetSearch);
        if ($term === '') {
            return;
        }

        $escapedTerm = addcslashes($term, '\\%_');

        $this->searchResults = Asset::query()
            ->with([
                'product' => static fn ($query) => $query->withTrashed(),
                'contract',
            ])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($escapedTerm) {
                $query->where('serial', 'like', "%{$escapedTerm}%")
                    ->orWhere('asset_tag', 'like', "%{$escapedTerm}%");
            })
            ->whereNotIn('id', $this->linkedAssetIds)
            ->limit(10)
            ->get()
            ->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'serial' => $asset->serial,
                'product_name' => $asset->product->name,
                'current_contract_identifier' => $asset->contract_id !== null ? $asset->contract->identifier : null,
            ])
            ->all();
    }

    public function linkAsset(int $assetId): void
    {
        Gate::authorize('inventory.manage');

        if (in_array($assetId, $this->linkedAssetIds, true)) {
            return;
        }

        $asset = Asset::query()
            ->with([
                'product' => static fn ($query) => $query->withTrashed(),
                'contract',
            ])
            ->whereNull('deleted_at')
            ->findOrFail($assetId);

        if (
            $asset->contract_id !== null
            && $asset->contract_id !== $this->contractId
            && $this->pendingReassignAssetId !== $asset->id
        ) {
            $this->pendingReassignAssetId = $asset->id;

            $contractIdentifier = $asset->contract->identifier;
            $this->toastWarning(
                "Este activo ya está vinculado al contrato {$contractIdentifier}. Haz clic en \"Vincular\" otra vez para reasignarlo."
            );

            return;
        }

        $this->pendingReassignAssetId = null;
        $this->linkedAssetIds[] = $asset->id;
        $this->linkedAssets[] = [
            'id' => $asset->id,
            'serial' => $asset->serial,
            'product_name' => $asset->product->name,
        ];

        $this->assetSearch = '';
        $this->searchResults = [];
    }

    public function unlinkAsset(int $assetId): void
    {
        Gate::authorize('inventory.manage');

        if ($this->pendingReassignAssetId === $assetId) {
            $this->pendingReassignAssetId = null;
        }

        $this->linkedAssetIds = array_values(array_filter(
            $this->linkedAssetIds,
            static fn (int $id): bool => $id !== $assetId
        ));

        $this->linkedAssets = array_values(array_filter(
            $this->linkedAssets,
            static fn (array $asset): bool => $asset['id'] !== $assetId
        ));
    }

    public function save(): mixed
    {
        Gate::authorize('inventory.manage');

        $validated = $this->validate();

        try {
            return DB::transaction(function () use ($validated) {
                if ($this->contractId === null) {
                    $contract = Contract::query()->create([
                        'identifier' => $validated['identifier'],
                        'type' => $validated['type'],
                        'supplier_id' => $validated['supplier_id'],
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                        'notes' => $validated['notes'] ?: null,
                    ]);

                    // Link assets to this new contract
                    if (count($this->linkedAssetIds) > 0) {
                        Asset::query()
                            ->whereIn('id', $this->linkedAssetIds)
                            ->whereNull('deleted_at')
                            ->update(['contract_id' => $contract->id]);
                    }

                    return redirect()
                        ->route('inventory.contracts.index')
                        ->with('status', 'Contrato creado.');
                }

                $contract = Contract::query()->findOrFail($this->contractId);
                $contract->identifier = $validated['identifier'];
                $contract->type = $validated['type'];
                $contract->supplier_id = $validated['supplier_id'];
                $contract->start_date = $validated['start_date'];
                $contract->end_date = $validated['end_date'];
                $contract->notes = $validated['notes'] ?: null;
                $contract->save();

                // Update asset links: first remove all, then add current selection
                Asset::query()
                    ->where('contract_id', $contract->id)
                    ->whereNull('deleted_at')
                    ->update(['contract_id' => null]);

                if (count($this->linkedAssetIds) > 0) {
                    Asset::query()
                        ->whereIn('id', $this->linkedAssetIds)
                        ->whereNull('deleted_at')
                        ->update(['contract_id' => $contract->id]);
                }

                return redirect()
                    ->route('inventory.contracts.index')
                    ->with('status', 'Contrato actualizado.');
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateIdentifierException($exception)) {
                $this->addError('identifier', 'Ya existe un contrato con este identificador.');

                return null;
            }

            throw $exception;
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.inventory.contracts.contract-form', [
            'isEdit' => (bool) $this->contractId,
            'suppliers' => $this->suppliers,
            'types' => [
                ['value' => Contract::TYPE_PURCHASE, 'label' => 'Compra'],
                ['value' => Contract::TYPE_LEASE, 'label' => 'Arrendamiento'],
            ],
        ]);
    }

    private function isDuplicateIdentifierException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        $driverCode = (int) ($errorInfo[1] ?? 0);

        return $driverCode === 1062;
    }
}
