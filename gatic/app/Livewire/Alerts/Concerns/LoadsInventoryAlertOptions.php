<?php

namespace App\Livewire\Alerts\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

trait LoadsInventoryAlertOptions
{
    protected function getAlertLocations(): Collection
    {
        return Location::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function getAlertCategories(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function getAlertBrands(): Collection
    {
        return Brand::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
