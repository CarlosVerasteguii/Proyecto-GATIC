<?php

namespace App\Livewire\Admin\Trash;

use App\Actions\Trash\EmptyTrash;
use App\Actions\Trash\PurgeTrashedItem;
use App\Actions\Trash\RestoreTrashedItem;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Story 8.4: Admin trash management for Products, Assets, and Employees.
 *
 * Provides tabs to view/restore/purge soft-deleted items.
 */
#[Layout('layouts.app')]
class TrashIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    /**
     * Allowed tabs/types for this trash view.
     *
     * @var list<string>
     */
    private const TABS = ['products', 'assets', 'employees'];

    public string $tab = 'products';

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('admin-only');
    }

    public function updatedSearch(): void
    {
        Gate::authorize('admin-only');

        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('admin-only');

        if (! in_array($tab, self::TABS, true)) {
            abort(404);
        }

        $this->tab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function restore(string $type, int $id): void
    {
        Gate::authorize('admin-only');
        $this->assertValidType($type);

        $action = new RestoreTrashedItem;
        $result = $action->execute($type, $id, auth()->id());

        if ($result['success']) {
            $this->toastSuccess($result['message']);
        } else {
            $this->toastError($result['message']);
        }

        $this->resetPage();
    }

    public function purge(string $type, int $id): void
    {
        Gate::authorize('admin-only');
        $this->assertValidType($type);

        $action = new PurgeTrashedItem;
        $result = $action->execute($type, $id, auth()->id());

        if ($result['success']) {
            $this->toastSuccess($result['message']);
        } else {
            $this->toastError($result['message']);
        }

        $this->resetPage();
    }

    public function emptyTrash(): void
    {
        Gate::authorize('admin-only');

        $action = new EmptyTrash;
        $result = $action->execute($this->tab, auth()->id());

        if ($result['success']) {
            $this->toastSuccess($result['message']);
        } else {
            $this->toastError($result['message']);
        }

        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        $escapedSearch = $this->search !== '' ? $this->escapeLike(trim($this->search)) : null;
        $perPage = (int) config('gatic.ui.pagination.per_page');

        return view('livewire.admin.trash.trash-index', [
            'products' => $this->tab === 'products'
                ? Product::query()
                    ->onlyTrashed()
                    ->with(['category', 'brand'])
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    })
                    ->orderByDesc('deleted_at')
                    ->paginate($perPage)
                : null,
            'assets' => $this->tab === 'assets'
                ? Asset::query()
                    ->onlyTrashed()
                    ->with(['product', 'location'])
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->where(function ($q) use ($escapedSearch) {
                            $q->whereRaw("serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                                ->orWhereRaw("asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                        });
                    })
                    ->orderByDesc('deleted_at')
                    ->paginate($perPage)
                : null,
            'employees' => $this->tab === 'employees'
                ? Employee::query()
                    ->onlyTrashed()
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->where(function ($q) use ($escapedSearch) {
                            $q->whereRaw("rpe like ? escape '\\\\'", ["%{$escapedSearch}%"])
                                ->orWhereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                        });
                    })
                    ->orderByDesc('deleted_at')
                    ->paginate($perPage)
                : null,
        ]);
    }

    private function assertValidType(string $type): void
    {
        if (! in_array($type, self::TABS, true)) {
            abort(404);
        }
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
