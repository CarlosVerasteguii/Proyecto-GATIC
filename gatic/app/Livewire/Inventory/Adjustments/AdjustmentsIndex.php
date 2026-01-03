<?php

namespace App\Livewire\Inventory\Adjustments;

use App\Models\InventoryAdjustment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AdjustmentsIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        Gate::authorize('admin-only');

        $adjustments = InventoryAdjustment::query()
            ->with('user')
            ->withCount('entries')
            ->latest()
            ->paginate(20);

        return view('livewire.inventory.adjustments.adjustments-index', [
            'adjustments' => $adjustments,
        ]);
    }
}

