<?php

namespace App\Livewire\Search;

use App\Actions\Search\SearchInventory;
use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class InventorySearch extends Component
{
    private const MIN_CHARS = 2;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @var Collection<int, Product>
     */
    public Collection $products;

    /**
     * @var Collection<int, Asset>
     */
    public Collection $assets;

    public bool $showMinCharsMessage = false;

    public bool $showNoResultsMessage = false;

    public function mount(): void
    {
        Gate::authorize('inventory.view');

        $this->products = collect();
        $this->assets = collect();

        // Handle initial search from URL query string (server-side redirect)
        $this->performSearch();
    }

    public function submitSearch(): void
    {
        $this->performSearch();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->performSearch();
    }

    private function performSearch(): void
    {
        Gate::authorize('inventory.view');

        $normalizedSearch = trim($this->search);

        // Reset state
        $this->products = collect();
        $this->assets = collect();
        $this->showMinCharsMessage = false;
        $this->showNoResultsMessage = false;

        // Check minimum characters
        if ($normalizedSearch !== '' && mb_strlen($normalizedSearch) < self::MIN_CHARS) {
            $this->showMinCharsMessage = true;

            return;
        }

        // Don't search if empty
        if ($normalizedSearch === '') {
            return;
        }

        // Execute search
        $searchAction = app(SearchInventory::class);
        $results = $searchAction->execute($normalizedSearch);

        $this->products = $results['products'];
        $this->assets = $results['assets'];
        $exactMatch = $results['exactMatch'];

        // If exact match found, redirect to asset detail
        if ($exactMatch instanceof Asset) {
            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $exactMatch->product_id,
                'asset' => $exactMatch->id,
            ]);

            return;
        }

        // Show no results message if search was performed but nothing found
        if ($this->products->isEmpty() && $this->assets->isEmpty()) {
            $this->showNoResultsMessage = true;
        }
    }

    public function render(): View
    {
        return view('livewire.search.inventory-search', [
            'minChars' => self::MIN_CHARS,
        ]);
    }
}
