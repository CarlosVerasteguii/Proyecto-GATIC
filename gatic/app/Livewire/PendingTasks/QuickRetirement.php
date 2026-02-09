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

class QuickRetirement extends Component
{
    use InteractsWithToasts;

    public bool $showModal = false;

    public string $mode = 'serials';

    public string $serialsInput = '';

    public ?int $productId = null;

    public ?string $quantity = null;

    public string $reason = '';

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

    public function updatedMode(): void
    {
        $this->serialsInput = '';
        $this->productId = null;
        $this->quantity = null;
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'mode' => ['required', 'string', Rule::in(['serials', 'product_quantity'])],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->mode === 'serials') {
            $rules['serialsInput'] = ['required', 'string'];
        } else {
            $rules['productId'] = [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ];
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
            'mode.required' => 'Selecciona el modo de retiro.',
            'mode.in' => 'El modo de retiro no es válido.',
            'serialsInput.required' => 'Pega al menos un serial.',
            'productId.required' => 'Selecciona un producto.',
            'productId.exists' => 'El producto seleccionado no existe o fue eliminado.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser mayor a cero.',
            'reason.required' => 'El motivo de retiro es obligatorio.',
            'reason.min' => 'El motivo de retiro es demasiado corto.',
            'reason.max' => 'El motivo de retiro es demasiado largo.',
            'note.max' => 'La nota es demasiado larga.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate();

        $payload = [
            'schema' => 'fp03.quick_capture',
            'version' => 1,
            'kind' => 'quick_retirement',
            'product' => null,
            'items' => [
                'type' => $this->mode === 'serials' ? 'serialized' : 'quantity',
            ],
            'reason' => trim($this->reason),
            'note' => is_string($this->note) && trim($this->note) !== '' ? trim($this->note) : null,
        ];

        $description = 'Retiro rápido';

        if ($this->mode === 'serials') {
            $serials = $this->parseSerials($this->serialsInput);
            $this->validateSerials($serials, field: 'serialsInput');

            $payload['items']['serials'] = $serials;
            $description = 'Retiro rápido: '.count($serials).' serial(es)';
        } else {
            $product = collect($this->products)->firstWhere('id', $this->productId);
            if (! is_array($product)) {
                throw ValidationException::withMessages([
                    'productId' => ['Selecciona un producto válido.'],
                ]);
            }

            if (($product['is_serialized'] ?? false) === true) {
                throw ValidationException::withMessages([
                    'productId' => ['El producto es serializado. Usa el modo "Por seriales".'],
                ]);
            }

            $qty = (int) $this->quantity;

            $payload['product'] = [
                'mode' => 'existing',
                'id' => (int) $product['id'],
                'name' => (string) $product['name'],
                'is_serialized' => false,
            ];
            $payload['items']['quantity'] = $qty;

            $description = 'Retiro rápido: '.(string) $product['name']." (Cantidad: {$qty})";
        }

        /** @var int $userId */
        $userId = (int) Auth::id();

        try {
            $task = (new CreateQuickCapturePendingTask)->execute([
                'type' => PendingTaskType::Retirement->value,
                'description' => $description,
                'creator_user_id' => $userId,
                'payload' => $payload,
            ]);
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $errorId = app(ErrorReporter::class)->report($e, request());
            $this->toastError(
                message: 'Ocurrió un error al registrar el retiro rápido.',
                title: 'Error inesperado',
                errorId: $errorId,
            );

            return;
        }

        $this->close();

        $this->toast(
            type: 'success',
            title: 'Retiro rápido registrado',
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
        $this->mode = 'serials';
        $this->serialsInput = '';
        $this->productId = null;
        $this->quantity = null;
        $this->reason = '';
        $this->note = '';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $selected = null;
        if ($this->mode === 'product_quantity' && $this->productId !== null) {
            $selected = collect($this->products)->firstWhere('id', $this->productId);
        }

        return view('livewire.pending-tasks.quick-retirement', [
            'selectedProduct' => $selected,
        ]);
    }
}
