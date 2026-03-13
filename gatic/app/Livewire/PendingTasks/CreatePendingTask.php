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

    /**
     * @return list<array{
     *     value: string,
     *     label: string,
     *     summary: string,
     *     direction_label: string,
     *     line_support: string,
     *     flow_hint: string,
     *     quick_hint: string|null,
     *     direction_tone: string
     * }>
     */
    private function buildTypeCards(): array
    {
        return array_map(function (PendingTaskType $type): array {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'summary' => match ($type) {
                    PendingTaskType::StockIn => 'Prepara entradas de inventario para validarlas antes de aplicar el movimiento.',
                    PendingTaskType::StockOut => 'Agrupa salidas por cantidad cuando necesitas revisar el lote antes de procesarlo.',
                    PendingTaskType::Assign => 'Organiza asignaciones formales con empleado y notas por renglón.',
                    PendingTaskType::Loan => 'Captura préstamos con contexto operativo y seguimiento antes de finalizar.',
                    PendingTaskType::Return => 'Prepara devoluciones para validar cantidades o activos recibidos.',
                    PendingTaskType::Retirement => 'Documenta bajas por cantidad cuando el retiro necesita revisión previa.',
                },
                'direction_label' => $type->quantityDirection() === 'in' ? 'Entrada' : 'Salida',
                'line_support' => $type->supportsSerialized()
                    ? 'Renglones por cantidad y serializados'
                    : 'Renglones por cantidad',
                'flow_hint' => $type->quantityDirection() === 'in'
                    ? 'Impacta inventario como entrada.'
                    : 'Impacta inventario como salida.',
                'quick_hint' => match ($type) {
                    PendingTaskType::StockIn => 'Si necesitas registrar seriales nuevos o una captura mínima, usa "Carga rápida".',
                    PendingTaskType::Retirement => 'Si el retiro parte de seriales o requiere captura mínima, usa "Retiro rápido".',
                    default => null,
                },
                'direction_tone' => $type->quantityDirection() === 'in' ? 'success' : 'warning',
            ];
        }, PendingTaskType::cases());
    }

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

        $typeCards = $this->buildTypeCards();
        $selectedTypeCard = collect($typeCards)->firstWhere('value', $this->type);

        return view('livewire.pending-tasks.create-pending-task', [
            'types' => PendingTaskType::cases(),
            'typeCards' => $typeCards,
            'selectedTypeCard' => is_array($selectedTypeCard) ? $selectedTypeCard : null,
        ]);
    }
}
