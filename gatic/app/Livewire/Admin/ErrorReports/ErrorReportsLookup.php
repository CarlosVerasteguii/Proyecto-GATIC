<?php

namespace App\Livewire\Admin\ErrorReports;

use App\Models\ErrorReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ErrorReportsLookup extends Component
{
    #[Url(as: 'error')]
    public string $errorId = '';

    public bool $searched = false;

    public ?int $reportId = null;

    public function mount(): void
    {
        Gate::authorize('admin-only');

        $this->errorId = trim($this->errorId);

        if ($this->errorId !== '') {
            $this->search();
        }
    }

    public function search(): void
    {
        Gate::authorize('admin-only');

        $this->errorId = trim($this->errorId);
        $this->validate([
            'errorId' => ['required', 'string', 'max:100'],
        ], [
            'errorId.required' => 'Ingresa un error ID para realizar la búsqueda.',
        ]);

        $this->searched = true;
        $this->reportId = null;

        $report = ErrorReport::query()
            ->where('error_id', $this->errorId)
            ->first();

        $this->reportId = $report?->id;
    }

    public function updatedErrorId(): void
    {
        $this->resetValidation('errorId');
        $this->searched = false;
        $this->reportId = null;
    }

    public function clearLookup(): void
    {
        $this->reset(['errorId', 'searched', 'reportId']);
        $this->resetValidation();
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
