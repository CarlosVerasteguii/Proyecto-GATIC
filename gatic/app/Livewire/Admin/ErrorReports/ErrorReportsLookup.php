<?php

namespace App\Livewire\Admin\ErrorReports;

use App\Models\ErrorReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ErrorReportsLookup extends Component
{
    public string $errorId = '';

    public bool $searched = false;

    public ?int $reportId = null;

    public function mount(): void
    {
        Gate::authorize('admin-only');
    }

    public function search(): void
    {
        Gate::authorize('admin-only');

        $this->searched = true;
        $this->reportId = null;

        $errorId = trim($this->errorId);
        if ($errorId === '') {
            return;
        }

        $report = ErrorReport::query()
            ->where('error_id', $errorId)
            ->first();

        $this->reportId = $report?->id;
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        $report = null;
        if ($this->reportId) {
            $report = ErrorReport::query()->find($this->reportId);
        }

        return view('livewire.admin.error-reports.error-reports-lookup', [
            'report' => $report,
        ]);
    }
}
