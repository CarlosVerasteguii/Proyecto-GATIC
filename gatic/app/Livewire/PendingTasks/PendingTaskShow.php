<?php

namespace App\Livewire\PendingTasks;

use App\Actions\PendingTasks\AddLineToTask;
use App\Actions\PendingTasks\MarkTaskAsReady;
use App\Actions\PendingTasks\RemoveLineFromTask;
use App\Actions\PendingTasks\UpdateTaskLine;
use App\Enums\PendingTaskLineType;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class PendingTaskShow extends Component
{
    public int $pendingTask;

    public ?PendingTask $task = null;

    // Modal state
    public bool $showLineModal = false;

    public ?int $editingLineId = null;

    // Form fields
    public string $lineType = '';

    public ?int $productId = null;

    public string $serial = '';

    public string $assetTag = '';

    public ?int $quantity = null;

    public ?int $employeeId = null;

    public string $note = '';

    // Product selection
    /** @var array<int, array{id: int, name: string, is_serialized: bool}> */
    public array $products = [];

    /** @var array<string, list<int>> */
    public array $duplicates = [];

    public function mount(int $pendingTask): void
    {
        Gate::authorize('inventory.manage');

        $this->pendingTask = $pendingTask;
        $this->loadTask();
        $this->loadProducts();
    }

    private function loadTask(): void
    {
        $this->task = PendingTask::with(['creator', 'lines.product', 'lines.employee'])
            ->findOrFail($this->pendingTask);

        $this->duplicates = $this->task->getDuplicateIdentifiers();
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
                'id' => $p->id,
                'name' => $p->name,
                'is_serialized' => (bool) $p->getAttribute('is_serialized'),
            ])
            ->toArray();
    }

    public function openAddLineModal(): void
    {
        if (! $this->task?->isDraft()) {
            return;
        }

        $this->resetForm();
        $this->editingLineId = null;
        $this->showLineModal = true;
    }

    public function openEditLineModal(int $lineId): void
    {
        if (! $this->task?->isDraft()) {
            return;
        }

        $line = PendingTaskLine::find($lineId);
        if (! $line || $line->pending_task_id !== $this->pendingTask) {
            return;
        }

        $this->editingLineId = $lineId;
        $this->lineType = $line->line_type->value;
        $this->productId = $line->product_id;
        $this->serial = $line->serial ?? '';
        $this->assetTag = $line->asset_tag ?? '';
        $this->quantity = $line->quantity;
        $this->employeeId = $line->employee_id;
        $this->note = $line->note;
        $this->showLineModal = true;
    }

    public function closeModal(): void
    {
        $this->showLineModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->lineType = '';
        $this->productId = null;
        $this->serial = '';
        $this->assetTag = '';
        $this->quantity = null;
        $this->employeeId = null;
        $this->note = '';
        $this->editingLineId = null;
        $this->resetErrorBag();
    }

    #[On('employee-selected')]
    public function onEmployeeSelected(?int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function updatedProductId(): void
    {
        // Auto-set line type based on product category
        if ($this->productId) {
            $product = collect($this->products)->firstWhere('id', $this->productId);
            if ($product) {
                $this->lineType = $product['is_serialized']
                    ? PendingTaskLineType::Serialized->value
                    : PendingTaskLineType::Quantity->value;
            }
        }
    }

    public function saveLine(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task?->isDraft()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'La tarea no está en estado borrador.',
            ]);

            return;
        }

        try {
            if ($this->editingLineId) {
                $action = new UpdateTaskLine;
                $result = $action->execute($this->editingLineId, [
                    'line_type' => $this->lineType,
                    'product_id' => $this->productId,
                    'serial' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->serial : null,
                    'asset_tag' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->assetTag : null,
                    'quantity' => $this->lineType === PendingTaskLineType::Quantity->value ? (int) $this->quantity : null,
                    'employee_id' => $this->employeeId,
                    'note' => $this->note,
                ]);

                $message = 'Renglón actualizado.';
            } else {
                $action = new AddLineToTask;
                $result = $action->execute([
                    'pending_task_id' => $this->pendingTask,
                    'line_type' => $this->lineType,
                    'product_id' => $this->productId,
                    'serial' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->serial : null,
                    'asset_tag' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->assetTag : null,
                    'quantity' => $this->lineType === PendingTaskLineType::Quantity->value ? (int) $this->quantity : null,
                    'employee_id' => $this->employeeId,
                    'note' => $this->note,
                ]);

                $message = 'Renglón añadido.';
            }

            if ($result['has_duplicates']) {
                $message .= ' (Duplicado detectado)';
            }

            $this->closeModal();
            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => $message,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function removeLine(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task?->isDraft()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'La tarea no está en estado borrador.',
            ]);

            return;
        }

        try {
            $action = new RemoveLineFromTask;
            $action->execute($lineId);

            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Renglón eliminado.',
            ]);
        } catch (ValidationException $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->errors()['pending_task_id'][0] ?? $e->getMessage(),
            ]);
        }
    }

    public function markAsReady(): void
    {
        Gate::authorize('inventory.manage');

        try {
            $action = new MarkTaskAsReady;
            $action->execute($this->pendingTask);

            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Tarea marcada como lista. Ya no puedes editar renglones.',
            ]);
        } catch (ValidationException $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->errors()['lines'][0] ?? $e->errors()['status'][0] ?? $e->getMessage(),
            ]);
        }
    }

    public function isDuplicate(int $lineId): bool
    {
        foreach ($this->duplicates as $lineIds) {
            if (in_array($lineId, $lineIds, true)) {
                return true;
            }
        }

        return false;
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.pending-tasks.pending-task-show', [
            'lineTypes' => PendingTaskLineType::cases(),
        ]);
    }
}
