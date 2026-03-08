<?php

namespace App\Livewire\Search;

use App\Actions\Search\SearchInventory;
use App\Models\Asset;
use App\Models\Product;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

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

    public ?string $errorId = null;

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

    public function retrySearch(): void
    {
        $this->performSearch();
    }

    private function performSearch(): void
    {
        Gate::authorize('inventory.view');

        $normalizedSearch = trim($this->search);

        $this->resetSearchState();

        if ($normalizedSearch !== '' && mb_strlen($normalizedSearch) < self::MIN_CHARS) {
            $this->showMinCharsMessage = true;

            return;
        }

        if ($normalizedSearch === '') {
            return;
        }

        try {
            $results = app(SearchInventory::class)->execute($normalizedSearch);
        } catch (Throwable $exception) {
            $this->errorId = app(ErrorReporter::class)->report($exception, request());

            return;
        }

        $this->products = $results['products'];
        $this->assets = $results['assets'];
        $exactMatch = $results['exactMatch'];

        if ($exactMatch instanceof Asset) {
            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $exactMatch->product_id,
                'asset' => $exactMatch->id,
            ]);

            return;
        }

        if ($this->products->isEmpty() && $this->assets->isEmpty()) {
            $this->showNoResultsMessage = true;
        }
    }

    private function resetSearchState(): void
    {
        $this->products = collect();
        $this->assets = collect();
        $this->showMinCharsMessage = false;
        $this->showNoResultsMessage = false;
        $this->errorId = null;
    }

    public function render(): View
    {
        $productsCount = $this->products->count();
        $assetsCount = $this->assets->count();

        return view('livewire.search.inventory-search', [
            'minChars' => self::MIN_CHARS,
            'hasSearch' => trim($this->search) !== '',
            'productsCount' => $productsCount,
            'assetsCount' => $assetsCount,
            'totalResults' => $productsCount + $assetsCount,
        ]);
    }
}
