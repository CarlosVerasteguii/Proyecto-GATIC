<?php

namespace App\Livewire\PendingTasks;

use App\Actions\PendingTasks\CreatePendingTask as CreatePendingTaskAction;
use App\Enums\PendingTaskType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreatePendingTask extends Component
{
    public string $type = '';

    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('inventory.manage');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', PendingTaskType::values())],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'type.required' => 'El tipo de operación es obligatorio.',
            'type.in' => 'El tipo de operación seleccionado no es válido.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate();

        $action = new CreatePendingTaskAction;
        $task = $action->execute([
            'type' => $this->type,
            'description' => $this->description ?: null,
            'creator_user_id' => auth()->id(),
        ]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Tarea creada. Añade renglones para comenzar.',
        ]);

        $this->redirect(route('pending-tasks.show', $task->id), navigate: true);
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.pending-tasks.create-pending-task', [
            'types' => PendingTaskType::cases(),
        ]);
    }
}
