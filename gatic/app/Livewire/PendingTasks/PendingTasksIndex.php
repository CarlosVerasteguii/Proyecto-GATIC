<?php

namespace App\Livewire\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\PendingTask;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PendingTasksIndex extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public function mount(): void
    {
        Gate::authorize('inventory.manage');
    }

    #[On('pending-tasks:refresh')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->statusFilter !== '' || $this->typeFilter !== '';
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $tasks = PendingTask::query()
            ->with('creator')
            ->withCount('lines')
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->typeFilter !== '', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.pending-tasks.pending-tasks-index', [
            'tasks' => $tasks,
            'statuses' => PendingTaskStatus::cases(),
            'types' => PendingTaskType::cases(),
        ]);
    }
}
