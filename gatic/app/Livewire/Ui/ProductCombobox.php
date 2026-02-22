<?php

namespace App\Livewire\Ui;

use App\Actions\Products\SearchProducts;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Support\Errors\ErrorReporter;
use App\Support\Ui\ReturnToPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class ProductCombobox extends Component
{
    use InteractsWithToasts;

    #[Modelable]
    public ?int $productId = null;

    public string $productLabel = '';

    public bool $productIsSerialized = false;

    public string $search = '';

    public bool $showDropdown = false;

    public ?string $errorId = null;

    public ?string $inputId = null;

    public ?string $returnTo = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $productId = null, ?string $inputId = null, ?string $returnTo = null): void
    {
        Gate::authorize('inventory.manage');

        $this->inputId = $inputId;
        $this->returnTo = ReturnToPath::sanitize($returnTo) ?? ReturnToPath::browserCurrent(['created_id']);

        if ($productId !== null) {
            try {
                $product = $this->findProduct($productId);
            } catch (Throwable $exception) {
                $this->clearProductData();
                $this->reportException($exception);

                return;
            }

            if ($product !== null) {
                $this->setProductData($product);

                return;
            }
        }

        $this->autoselectCreatedProductFromQuery();
    }

    public function updatedProductId(?int $productId): void
    {
        Gate::authorize('inventory.manage');

        if ($productId === null) {
            $this->clearProductData();

            return;
        }

        try {
            $product = $this->findProduct($productId);
        } catch (Throwable $exception) {
            $this->clearProductData();
            $this->reportException($exception);

            return;
        }

        if (! $product) {
            $this->clearProductData();

            return;
        }

        $this->setProductData($product);
    }

    public function updatedSearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function selectProduct(int $productId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $product = $this->findProductOrFail($productId);
        } catch (ModelNotFoundException) {
            $this->toastError('Producto no encontrado.', title: 'Producto no encontrado');
            $this->showDropdown = true;

            return;
        } catch (Throwable $exception) {
            $this->reportException($exception);
            $this->showDropdown = true;

            return;
        }

        $this->setProductData($product);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->clearProductData();
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function closeDropdown(): void
    {
        Gate::authorize('inventory.manage');

        $this->showDropdown = false;
    }

    public function retrySearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $normalizedSearch = Product::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;
        $componentId = $this->buildDomIdSuffix();

        $products = $this->showDropdown ? $this->getSuggestions() : collect();

        $showMinCharsMessage = $searchLength > 0 && $searchLength < self::MIN_SEARCH_LENGTH;
        $showNoResults = $searchLength >= self::MIN_SEARCH_LENGTH && $products->isEmpty();

        $createUrl = null;
        if (
            $showNoResults
            && Gate::allows('inventory.manage')
            && is_string($normalizedSearch)
            && $normalizedSearch !== ''
        ) {
            $params = ['prefill' => $normalizedSearch];
            if (is_string($this->returnTo) && $this->returnTo !== '') {
                $params['returnTo'] = $this->returnTo;
            }

            $createUrl = route('inventory.products.create', $params);
        }

        return view('livewire.ui.product-combobox', [
            'products' => $products,
            'errorId' => $this->errorId,
            'showMinCharsMessage' => $showMinCharsMessage,
            'showNoResults' => $showNoResults,
            'createUrl' => $createUrl,
            'inputId' => is_string($this->inputId) && $this->inputId !== ''
                ? $this->inputId
                : 'product-input-'.$componentId,
            'listboxId' => 'product-listbox-'.$componentId,
            'optionIdPrefix' => 'product-option-'.$componentId.'-',
            'createOptionId' => 'product-option-create-'.$componentId,
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, is_serialized: bool}>
     */
    private function getSuggestions(): Collection
    {
        try {
            $action = app(SearchProducts::class);

            return $action->execute($this->search, self::MAX_RESULTS);
        } catch (Throwable $exception) {
            $this->reportException($exception);

            return collect();
        }
    }

    private function setProductData(Product $product): void
    {
        $this->productId = (int) $product->id;
        $this->productLabel = (string) $product->name;
        $this->productIsSerialized = (bool) $product->category->is_serialized;
    }

    private function clearProductData(): void
    {
        $this->productId = null;
        $this->productLabel = '';
        $this->productIsSerialized = false;
    }

    private function autoselectCreatedProductFromQuery(): void
    {
        $rawCreatedId = request()->query('created_id');
        if (! is_string($rawCreatedId) && request()->headers->has('X-Livewire')) {
            $rawCreatedId = ReturnToPath::queryParamFromReferer('created_id');
        }
        if (! is_string($rawCreatedId)) {
            return;
        }

        $createdId = (string) $rawCreatedId;
        if (! ctype_digit($createdId)) {
            return;
        }

        try {
            $product = $this->findProduct((int) $createdId);
        } catch (Throwable $exception) {
            $this->clearProductData();
            $this->reportException($exception);

            return;
        }

        if ($product === null) {
            return;
        }

        $this->setProductData($product);
        $this->toastInfo('Producto creado y seleccionado.', title: 'Producto seleccionado');
    }

    private function findProduct(int $productId): ?Product
    {
        return Product::query()
            ->with('category')
            ->whereNull('products.deleted_at')
            ->whereHas('category', static fn ($query) => $query->whereNull('categories.deleted_at'))
            ->find($productId);
    }

    private function findProductOrFail(int $productId): Product
    {
        return Product::query()
            ->with('category')
            ->whereNull('products.deleted_at')
            ->whereHas('category', static fn ($query) => $query->whereNull('categories.deleted_at'))
            ->findOrFail($productId);
    }

    private function buildDomIdSuffix(): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->getId()) ?? 'component';
    }

    private function reportException(Throwable $exception): void
    {
        $this->errorId = app(ErrorReporter::class)->report($exception, request());

        $this->toastError(
            'Ocurrió un error inesperado.',
            title: 'Error inesperado',
            errorId: $this->errorId,
        );
    }
}
