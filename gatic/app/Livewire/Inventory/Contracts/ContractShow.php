<?php

namespace App\Livewire\Inventory\Contracts;

use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ContractShow extends Component
{
    public int $contractId;

    public ?Contract $contractModel = null;

    public function mount(string $contract): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($contract)) {
            abort(404);
        }

        $this->contractId = (int) $contract;

        $this->contractModel = Contract::query()
            ->with(['supplier', 'assets.product'])
            ->findOrFail($this->contractId);
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.contracts.contract-show', [
            'contract' => $this->contractModel,
        ]);
    }
}
