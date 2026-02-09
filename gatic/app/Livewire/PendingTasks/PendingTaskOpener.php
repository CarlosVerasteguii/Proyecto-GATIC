<?php

namespace App\Livewire\PendingTasks;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class PendingTaskOpener extends Component
{
    #[On('pending-tasks:open')]
    public function open(int $id): void
    {
        Gate::authorize('inventory.manage');

        $this->redirectRoute('pending-tasks.show', ['pendingTask' => $id], navigate: true);
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.pending-tasks.pending-task-opener');
    }
}
