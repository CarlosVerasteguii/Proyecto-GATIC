<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductForm extends Component
{
    public ?int $productId = null;

    public ?int $category_id = null;

    public ?int $brand_id = null;

    public string $name = '';

    public mixed $qty_total = null;

    public bool $categoryIsSerialized = false;

    /**
     * @var array<int, array{id:int, name:string, is_serialized:bool}>
     */
    public array $categories = [];

    /**
     * @var array<int, array{id:int, name:string}>
     */
    public array $brands = [];

    private ?int $originalCategoryId = null;

    public function mount(?string $product = null): void
    {
        Gate::authorize('inventory.manage');

        $this->categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'is_serialized'])
            ->map(static fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'is_serialized' => (bool) $category->is_serialized,
            ])
            ->all();

        $this->brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Brand $brand): array => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])
            ->all();

        if (! $product) {
            return;
        }

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $model = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        $this->name = $model->name;
        $this->category_id = $model->category_id;
        $this->originalCategoryId = $model->category_id;
        $this->brand_id = $model->brand_id;
        $this->qty_total = $model->qty_total;
        $this->categoryIsSerialized = (bool) $model->category?->is_serialized;
    }

    public function updatedCategoryId(?int $value): void
    {
        Gate::authorize('inventory.manage');

        if ($value === null) {
            $this->categoryIsSerialized = false;

            return;
        }

        $category = collect($this->categories)->firstWhere('id', $value);
        $this->categoryIsSerialized = (bool) ($category['is_serialized'] ?? false);

        if ($this->categoryIsSerialized) {
            $this->qty_total = null;
            $this->resetValidation('qty_total');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $categoryRules = [
            'required',
            'integer',
            Rule::exists('categories', 'id')->whereNull('deleted_at'),
        ];

        if ($this->productId !== null && $this->originalCategoryId !== null) {
            $categoryRules[] = Rule::in([$this->originalCategoryId]);
        }

        $qtyRules = ['nullable'];

        if (! $this->categoryIsSerialized) {
            $qtyRules = [
                'required',
                'integer',
                'min:0',
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => $categoryRules,
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->whereNull('deleted_at'),
            ],
            'qty_total' => $qtyRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'category_id.in' => 'La categoría no se puede cambiar.',
            'brand_id.exists' => 'La marca seleccionada no es válida.',
            'qty_total.required' => 'El stock total es obligatorio para productos por cantidad.',
            'qty_total.integer' => 'El stock total debe ser un número entero.',
            'qty_total.min' => 'El stock total debe ser 0 o mayor.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('inventory.manage');

        $this->name = Product::normalizeName($this->name) ?? '';

        $model = null;
        if ($this->productId !== null) {
            $model = Product::query()->with('category')->findOrFail($this->productId);
            $this->category_id = $model->category_id;
            $this->categoryIsSerialized = (bool) $model->category?->is_serialized;
        } else {
            $category = $this->category_id !== null
                ? collect($this->categories)->firstWhere('id', $this->category_id)
                : null;
            $this->categoryIsSerialized = (bool) ($category['is_serialized'] ?? false);
        }

        if ($this->categoryIsSerialized) {
            $this->qty_total = null;
        }

        $validated = $this->validate();

        if ($this->productId === null) {
            Product::query()->create([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'],
                'qty_total' => $this->categoryIsSerialized ? null : $validated['qty_total'],
            ]);

            return redirect()
                ->route('inventory.products.index')
                ->with('status', 'Producto creado.');
        }

        $model->name = $validated['name'];
        $model->brand_id = $validated['brand_id'];
        $model->qty_total = $this->categoryIsSerialized ? null : $validated['qty_total'];
        $model->save();

        return redirect()
            ->route('inventory.products.index')
            ->with('status', 'Producto actualizado.');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.inventory.products.product-form', [
            'isEdit' => (bool) $this->productId,
            'categories' => $this->categories,
            'brands' => $this->brands,
        ]);
    }
}
