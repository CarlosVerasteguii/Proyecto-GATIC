<?php

namespace App\Livewire\Alerts\Stock;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LowStockAlertsIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $alerts = Product::query()
            ->with([
                'category:id,name,is_serialized',
                'brand:id,name',
            ])
            ->lowStockQuantity()
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.alerts.stock.low-stock-alerts-index', [
            'alerts' => $alerts,
        ]);
    }
}
