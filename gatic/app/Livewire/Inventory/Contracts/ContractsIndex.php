<?php

namespace App\Livewire\Inventory\Contracts;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ContractsIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $supplierFilter = '';

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $suppliers = [];

    public function mount(): void
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
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $query = Contract::query()
            ->with('supplier')
            ->withCount('assets');

        if (trim($this->search) !== '') {
            $escapedSearch = addcslashes(trim($this->search), '\\%_');
            $query->where('identifier', 'like', "%{$escapedSearch}%");
        }

        if ($this->typeFilter !== '' && in_array($this->typeFilter, Contract::TYPES, true)) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->supplierFilter !== '') {
            $supplierId = (int) $this->supplierFilter;
            if ($supplierId > 0) {
                $query->where('supplier_id', $supplierId);
            }
        }

        return view('livewire.inventory.contracts.contracts-index', [
            'contracts' => $query->orderByDesc('created_at')->paginate(15),
        ]);
    }
}
