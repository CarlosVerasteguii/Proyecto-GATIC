<?php

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin-only audit log viewer (AC3, AC4).
 *
 * Provides a paginated list of audit events with filters:
 * - Date range (from/to)
 * - Actor (user)
 * - Action type
 * - Subject type (entity)
 */
#[Layout('layouts.app')]
class AuditLogsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'actor')]
    public ?int $actorId = null;

    #[Url(as: 'action')]
    public string $action = '';

    #[Url(as: 'subject')]
    public string $subjectType = '';

    /**
     * Currently selected log for detail view.
     */
    public ?int $selectedLogId = null;

    public function mount(): void
    {
        Gate::authorize('admin-only');
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedActorId(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedSubjectType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo', 'actorId', 'action', 'subjectType']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->dateFrom !== ''
            || $this->dateTo !== ''
            || $this->actorId !== null
            || $this->action !== ''
            || $this->subjectType !== '';
    }

    /**
     * Open the detail modal for a specific log.
     */
    public function showDetail(int $logId): void
    {
        $this->selectedLogId = $logId;
    }

    /**
     * Close the detail modal.
     */
    public function closeDetail(): void
    {
        $this->selectedLogId = null;
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        $logs = AuditLog::query()
            ->with('actor')
            ->when($this->dateFrom !== '', function (Builder $query) {
                $query->where('created_at', '>=', $this->dateFrom.' 00:00:00');
            })
            ->when($this->dateTo !== '', function (Builder $query) {
                $query->where('created_at', '<=', $this->dateTo.' 23:59:59');
            })
            ->when($this->actorId !== null, function (Builder $query) {
                $query->where('actor_user_id', $this->actorId);
            })
            ->when($this->action !== '', function (Builder $query) {
                $query->where('action', $this->action);
            })
            ->when($this->subjectType !== '', function (Builder $query) {
                $query->where('subject_type', $this->subjectType);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        // Get unique actors for filter dropdown
        $actors = User::query()
            ->whereIn('id', AuditLog::query()->distinct()->pluck('actor_user_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get unique subject types for filter dropdown
        $subjectTypes = AuditLog::query()
            ->distinct()
            ->pluck('subject_type')
            ->filter()
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => class_basename($type),
            ])
            ->sortBy('label')
            ->values();

        // Load selected log for detail view
        $selectedLog = null;
        if ($this->selectedLogId !== null) {
            $selectedLog = AuditLog::with('actor')->find($this->selectedLogId);
        }

        return view('livewire.admin.audit.audit-logs-index', [
            'logs' => $logs,
            'actors' => $actors,
            'actions' => AuditLog::ACTIONS,
            'actionLabels' => AuditLog::ACTION_LABELS,
            'subjectTypes' => $subjectTypes,
            'selectedLog' => $selectedLog,
        ]);
    }
}
