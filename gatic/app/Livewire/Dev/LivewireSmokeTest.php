<?php

namespace App\Livewire\Dev;

use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class LivewireSmokeTest extends Component
{
    use InteractsWithToasts;

    public int $count = 0;
    public bool $toggle = false;

    public string $slowResult = 'Sin ejecutar';
    public int $slowResultVersion = 0;

    public int $pollCount = 0;
    public string $lastUpdatedAtIso = '';

    public function mount(): void
    {
        $this->lastUpdatedAtIso = now()->toIso8601String();
    }

    public function increment(): void
    {
        $this->count++;
    }

    public function toastSuccessDemo(): void
    {
        $this->toastSuccess('Operación exitosa.');
    }

    public function toastErrorDemo(): void
    {
        $this->toastError('Ocurrió un error inesperado.', errorId: 'DEMO-001');
    }

    public function toggleWithUndo(): void
    {
        $previous = $this->toggle;
        $this->toggle = ! $this->toggle;

        $this->dispatch(
            'ui:toast',
            type: 'success',
            title: 'Cambio aplicado',
            message: 'Se aplicó el cambio. Puedes deshacerlo si fue accidental.',
            action: [
                'label' => 'Deshacer',
                'event' => 'ui:undo-toggle',
                'params' => [
                    'previous' => $previous,
                ],
            ],
        );
    }

    #[On('ui:undo-toggle')]
    public function undoToggle(bool $previous = false): void
    {
        $this->toggle = $previous;
        $this->toastInfo('Acción revertida.');
    }

    public function slowOperation(): void
    {
        sleep(5);

        $this->slowResultVersion++;
        $this->slowResult = 'Resultado #' . $this->slowResultVersion . ' (' . now()->format('H:i:s') . ')';
    }

    public function pollTick(): void
    {
        $this->pollCount++;
        $this->lastUpdatedAtIso = now()->toIso8601String();
    }

    public function render(): View
    {
        return view('livewire.dev.livewire-smoke-test');
    }
}
