<?php

namespace App\Livewire\PendingTasks;

use App\Actions\PendingTasks\CreateQuickCapturePendingTask;
use App\Enums\PendingTaskType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Asset;
use App\Models\Product;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class QuickStockIn extends Component
{
    use InteractsWithToasts;

    public bool $showModal = false;

    public string $productMode = 'existing';

    public ?int $productId = null;

    public string $placeholderProductName = '';

    /**
     * Only used when productMode=placeholder.
     * Values: "1" (serialized) or "0" (quantity).
     */
    public string $placeholderIsSerialized = '';

    public string $serialsInput = '';

    public ?string $quantity = null;

    public string $note = '';

    /** @var array<int, array{id: int, name: string, is_serialized: bool}> */
    public array $products = [];

    public int $maxLines = 200;

    public function mount(): void
    {
        Gate::authorize('inventory.manage');

        $this->maxLines = (int) config('gatic.pending_tasks.bulk_paste.max_lines', 200);
        $this->loadProducts();
        $this->resetForm();
    }

    private function loadProducts(): void
    {
        $this->products = Product::query()
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at')
            ->select('products.id', 'products.name', 'categories.is_serialized')
            ->orderBy('products.name')
            ->get()
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'is_serialized' => (bool) $p->getAttribute('is_serialized'),
            ])
            ->toArray();
    }

    public function open(): void
    {
        Gate::authorize('inventory.manage');

        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function updatedProductMode(): void
    {
        $this->productId = null;
        $this->placeholderProductName = '';
        $this->placeholderIsSerialized = '';
        $this->serialsInput = '';
        $this->quantity = null;
        $this->resetErrorBag();
    }

    public function updatedProductId(): void
    {
        $this->serialsInput = '';
        $this->quantity = null;
        $this->resetErrorBag();
    }

    public function updatedPlaceholderIsSerialized(): void
    {
        $this->serialsInput = '';
        $this->quantity = null;
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'productMode' => ['required', 'string', Rule::in(['existing', 'placeholder'])],
            'note' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->productMode === 'existing') {
            $rules['productId'] = [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ];
        } else {
            $rules['placeholderProductName'] = ['required', 'string', 'min:2', 'max:255'];
            $rules['placeholderIsSerialized'] = ['required', 'string', Rule::in(['0', '1'])];
        }

        $isSerialized = $this->resolveIsSerialized();
        if ($isSerialized === true) {
            $rules['serialsInput'] = ['required', 'string'];
        } elseif ($isSerialized === false) {
            $rules['quantity'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'productMode.required' => 'Selecciona el tipo de producto.',
            'productMode.in' => 'El tipo de producto no es válido.',
            'productId.required' => 'Selecciona un producto.',
            'productId.exists' => 'El producto seleccionado no existe o fue eliminado.',
            'placeholderProductName.required' => 'El nombre del producto es obligatorio.',
            'placeholderProductName.min' => 'El nombre del producto es demasiado corto.',
            'placeholderProductName.max' => 'El nombre del producto es demasiado largo.',
            'placeholderIsSerialized.required' => 'Indica si el producto es serializado.',
            'placeholderIsSerialized.in' => 'La opción de serializado no es válida.',
            'serialsInput.required' => 'Pega al menos un serial.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser mayor a cero.',
            'note.max' => 'La nota es demasiado larga.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate();

        $isSerialized = $this->resolveIsSerialized();
        if ($isSerialized === null) {
            throw ValidationException::withMessages([
                'productId' => ['Completa el tipo de producto para continuar.'],
            ]);
        }

        $product = $this->resolveProduct();
        $productName = $product['name'];

        $payload = [
            'schema' => 'fp03.quick_capture',
            'version' => 1,
            'kind' => 'quick_stock_in',
            'product' => [
                'mode' => $this->productMode,
                'id' => $product['id'],
                'name' => $productName,
                'is_serialized' => $isSerialized,
            ],
            'items' => [
                'type' => $isSerialized ? 'serialized' : 'quantity',
            ],
            'note' => is_string($this->note) && trim($this->note) !== '' ? trim($this->note) : null,
        ];

        if ($isSerialized) {
            $serials = $this->parseSerials($this->serialsInput);
            $this->validateSerials($serials, field: 'serialsInput');

            $payload['items']['serials'] = $serials;
        } else {
            $qty = (int) $this->quantity;
            $payload['items']['quantity'] = $qty;
        }

        $summary = $isSerialized
            ? (count($payload['items']['serials'] ?? [])).' serial(es)'
            : 'Cantidad: '.(int) ($payload['items']['quantity'] ?? 0);

        /** @var int $userId */
        $userId = (int) Auth::id();

        try {
            $task = (new CreateQuickCapturePendingTask)->execute([
                'type' => PendingTaskType::StockIn->value,
                'description' => "Carga rápida: {$productName} ({$summary})",
                'creator_user_id' => $userId,
                'payload' => $payload,
            ]);
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $errorId = app(ErrorReporter::class)->report($e, request());
            $this->toastError(
                message: 'Ocurrió un error al registrar la carga rápida.',
                title: 'Error inesperado',
                errorId: $errorId,
            );

            return;
        }

        $this->close();

        $this->toast(
            type: 'success',
            title: 'Carga rápida registrada',
            message: "Se creó la tarea pendiente #{$task->id}.",
            action: [
                'label' => 'Ver tarea',
                'event' => 'pending-tasks:open',
                'params' => ['id' => $task->id],
            ],
        );

        $this->dispatch('pending-tasks:refresh');
    }

    /**
     * @return array{id: int|null, name: string}
     */
    private function resolveProduct(): array
    {
        if ($this->productMode === 'placeholder') {
            return [
                'id' => null,
                'name' => trim($this->placeholderProductName),
            ];
        }

        $product = collect($this->products)->firstWhere('id', $this->productId);
        if (! is_array($product)) {
            throw ValidationException::withMessages([
                'productId' => ['Selecciona un producto válido.'],
            ]);
        }

        return [
            'id' => (int) $product['id'],
            'name' => (string) $product['name'],
        ];
    }

    private function resolveIsSerialized(): ?bool
    {
        if ($this->productMode === 'placeholder') {
            if ($this->placeholderIsSerialized !== '0' && $this->placeholderIsSerialized !== '1') {
                return null;
            }

            return $this->placeholderIsSerialized === '1';
        }

        if ($this->productId === null) {
            return null;
        }

        $product = collect($this->products)->firstWhere('id', $this->productId);
        if (! is_array($product)) {
            return null;
        }

        return (bool) ($product['is_serialized'] ?? false);
    }

    /**
     * @return list<string>
     */
    private function parseSerials(string $input): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $input) ?: [];
        $serials = [];

        foreach ($lines as $line) {
            $normalized = Asset::normalizeSerial($line);
            if ($normalized === null) {
                continue;
            }

            $serials[] = $normalized;
        }

        return $serials;
    }

    /**
     * @param  list<string>  $serials
     */
    private function validateSerials(array $serials, string $field): void
    {
        if ($serials === []) {
            throw ValidationException::withMessages([
                $field => ['Pega al menos un serial.'],
            ]);
        }

        if (count($serials) > max(1, $this->maxLines)) {
            throw ValidationException::withMessages([
                $field => ["Límite máximo: {$this->maxLines} seriales."],
            ]);
        }

        $counts = array_count_values($serials);
        $duplicates = array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
        if ($duplicates !== []) {
            $sample = array_slice($duplicates, 0, 5);
            $suffix = count($duplicates) > 5 ? '...' : '';
            throw ValidationException::withMessages([
                $field => ['Hay seriales duplicados: '.implode(', ', $sample).$suffix],
            ]);
        }

        foreach ($serials as $serial) {
            if (strlen($serial) > 255) {
                throw ValidationException::withMessages([
                    $field => ['Uno o más seriales exceden 255 caracteres.'],
                ]);
            }
        }
    }

    private function resetForm(): void
    {
        $this->productMode = 'existing';
        $this->productId = null;
        $this->placeholderProductName = '';
        $this->placeholderIsSerialized = '';
        $this->serialsInput = '';
        $this->quantity = null;
        $this->note = '';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $resolved = $this->resolveIsSerialized();
        $selected = null;
        if ($this->productMode === 'existing' && $this->productId !== null) {
            $selected = collect($this->products)->firstWhere('id', $this->productId);
        }

        return view('livewire.pending-tasks.quick-stock-in', [
            'resolvedIsSerialized' => $resolved,
            'selectedProduct' => $selected,
        ]);
    }
}
