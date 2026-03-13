<?php

namespace App\Livewire\Admin\Trash;

use App\Actions\Trash\EmptyTrash;
use App\Actions\Trash\PurgeTrashedItem;
use App\Actions\Trash\RestoreTrashedItem;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
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
     * @var array<string, array{
     *     label: string,
     *     icon: string,
     *     search_placeholder: string,
     *     description: string,
     *     empty_title: string,
     *     empty_description: string,
     *     empty_route: string,
     *     empty_action: string
     * }>
     */
    private const TABS = [
        'products' => [
            'label' => 'Productos',
            'icon' => 'bi-box-seam',
            'search_placeholder' => 'Buscar por nombre de producto…',
            'description' => 'Restaura o depura productos eliminados con visibilidad de categoría y marca.',
            'empty_title' => 'No hay productos eliminados',
            'empty_description' => 'Los productos enviados a papelera aparecerán aquí para restaurarlos o purgarlos cuando sea seguro hacerlo.',
            'empty_route' => 'inventory.products.index',
            'empty_action' => 'Ir a productos',
        ],
        'assets' => [
            'label' => 'Activos',
            'icon' => 'bi-hdd-stack',
            'search_placeholder' => 'Buscar por serial o asset tag…',
            'description' => 'Revisa el estado del activo antes de restaurarlo o eliminarlo permanentemente.',
            'empty_title' => 'No hay activos eliminados',
            'empty_description' => 'Cuando un activo pase a papelera se mostrará aquí con su estado, producto y fecha de eliminación.',
            'empty_route' => 'inventory.assets.index',
            'empty_action' => 'Ir a activos',
        ],
        'employees' => [
            'label' => 'Empleados',
            'icon' => 'bi-people',
            'search_placeholder' => 'Buscar por RPE o nombre…',
            'description' => 'Administra restauraciones o purgas de empleados eliminados sin perder trazabilidad operativa.',
            'empty_title' => 'No hay empleados eliminados',
            'empty_description' => 'La papelera conserva empleados eliminados hasta que decidas restaurarlos o depurarlos definitivamente.',
            'empty_route' => 'employees.index',
            'empty_action' => 'Ir a empleados',
        ],
    ];

    #[Url(as: 'tab')]
    public string $tab = 'products';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('admin-only');

        if (! $this->isValidTab($this->tab)) {
            $this->tab = 'products';
        }
    }

    public function updatedSearch(): void
    {
        Gate::authorize('admin-only');

        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('admin-only');

        if (! $this->isValidTab($tab)) {
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
        $tabCounts = $this->tabCounts();

        return view('livewire.admin.trash.trash-index', [
            'records' => $this->recordsForCurrentTab($escapedSearch, $perPage),
            'tabs' => self::TABS,
            'tabCounts' => $tabCounts,
            'totalCount' => array_sum($tabCounts),
            'currentTab' => self::TABS[$this->tab],
        ]);
    }

    private function assertValidType(string $type): void
    {
        if (! $this->isValidTab($type)) {
            abort(404);
        }
    }

    private function isValidTab(string $tab): bool
    {
        return array_key_exists($tab, self::TABS);
    }

    /**
     * @return array<string, int>
     */
    private function tabCounts(): array
    {
        return [
            'products' => Product::query()->onlyTrashed()->count(),
            'assets' => Asset::query()->onlyTrashed()->count(),
            'employees' => Employee::query()->onlyTrashed()->count(),
        ];
    }

    private function recordsForCurrentTab(?string $escapedSearch, int $perPage): LengthAwarePaginator
    {
        return match ($this->tab) {
            'products' => Product::query()
                ->onlyTrashed()
                ->with(['category', 'brand'])
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            'assets' => Asset::query()
                ->onlyTrashed()
                ->with(['product', 'location'])
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->where(function ($q) use ($escapedSearch) {
                        $q->whereRaw("serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                            ->orWhereRaw("asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    });
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            'employees' => Employee::query()
                ->onlyTrashed()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->where(function ($q) use ($escapedSearch) {
                        $q->whereRaw("rpe like ? escape '\\\\'", ["%{$escapedSearch}%"])
                            ->orWhereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    });
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            default => throw new \LogicException('Invalid admin trash tab.'),
        };
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
