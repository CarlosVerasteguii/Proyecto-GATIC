<?php

namespace App\Livewire\Catalogs\Trash;

use App\Actions\Trash\EmptyTrash;
use App\Actions\Trash\PurgeTrashedItem;
use App\Actions\Trash\RestoreTrashedItem;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CatalogsTrash extends Component
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
        'categories' => [
            'label' => 'Categorías',
            'icon' => 'bi-tags',
            'search_placeholder' => 'Buscar por nombre de categoría…',
            'description' => 'Revisa reglas de serialización y asset tag antes de restaurar o depurar categorías.',
            'empty_title' => 'No hay categorías eliminadas',
            'empty_description' => 'Las categorías eliminadas quedarán aquí hasta que decidas restaurarlas o purgarlas definitivamente.',
            'empty_route' => 'catalogs.categories.index',
            'empty_action' => 'Ir a categorías',
        ],
        'brands' => [
            'label' => 'Marcas',
            'icon' => 'bi-award',
            'search_placeholder' => 'Buscar por nombre de marca…',
            'description' => 'Mantén ordenada la referencia de marcas y valida dependencias antes de purgar.',
            'empty_title' => 'No hay marcas eliminadas',
            'empty_description' => 'Cuando una marca pase a papelera aparecerá aquí para recuperarla o eliminarla por completo.',
            'empty_route' => 'catalogs.brands.index',
            'empty_action' => 'Ir a marcas',
        ],
        'locations' => [
            'label' => 'Ubicaciones',
            'icon' => 'bi-geo-alt',
            'search_placeholder' => 'Buscar por nombre de ubicación…',
            'description' => 'Confirma dependencias con activos y movimientos antes de vaciar ubicaciones eliminadas.',
            'empty_title' => 'No hay ubicaciones eliminadas',
            'empty_description' => 'Las ubicaciones eliminadas se conservan aquí hasta que valides si deben restaurarse o purgarse.',
            'empty_route' => 'catalogs.locations.index',
            'empty_action' => 'Ir a ubicaciones',
        ],
        'suppliers' => [
            'label' => 'Proveedores',
            'icon' => 'bi-truck',
            'search_placeholder' => 'Buscar por nombre de proveedor…',
            'description' => 'Depura proveedores con claridad administrativa y conservando contexto de contacto.',
            'empty_title' => 'No hay proveedores eliminados',
            'empty_description' => 'Los proveedores eliminados permanecerán aquí mientras decides restaurarlos o depurarlos.',
            'empty_route' => 'catalogs.suppliers.index',
            'empty_action' => 'Ir a proveedores',
        ],
    ];

    #[Url(as: 'tab')]
    public string $tab = 'categories';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('catalogs.manage');

        if (! $this->isValidTab($this->tab)) {
            $this->tab = 'categories';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('catalogs.manage');

        if (! $this->isValidTab($tab)) {
            abort(404);
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    public function restore(string $type, int $id): void
    {
        Gate::authorize('catalogs.manage');

        if (! $this->isValidTab($type)) {
            abort(404);
        }

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
        Gate::authorize('catalogs.manage');

        if (! $this->isValidTab($type)) {
            abort(404);
        }

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
        Gate::authorize('catalogs.manage');

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
        Gate::authorize('catalogs.manage');

        $search = match ($this->tab) {
            'categories' => Category::normalizeName($this->search),
            'brands' => Brand::normalizeName($this->search),
            'locations' => Location::normalizeName($this->search),
            'suppliers' => Supplier::normalizeName($this->search),
            default => null,
        };

        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;
        $perPage = (int) config('gatic.ui.pagination.per_page');
        $tabCounts = $this->tabCounts();

        return view('livewire.catalogs.trash.catalogs-trash', [
            'records' => $this->recordsForCurrentTab($escapedSearch, $perPage),
            'tabs' => self::TABS,
            'tabCounts' => $tabCounts,
            'totalCount' => array_sum($tabCounts),
            'currentTab' => self::TABS[$this->tab],
        ]);
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
            'categories' => Category::query()->onlyTrashed()->count(),
            'brands' => Brand::query()->onlyTrashed()->count(),
            'locations' => Location::query()->onlyTrashed()->count(),
            'suppliers' => Supplier::query()->onlyTrashed()->count(),
        ];
    }

    private function recordsForCurrentTab(?string $escapedSearch, int $perPage): LengthAwarePaginator
    {
        return match ($this->tab) {
            'categories' => Category::query()
                ->onlyTrashed()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            'brands' => Brand::query()
                ->onlyTrashed()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            'locations' => Location::query()
                ->onlyTrashed()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            'suppliers' => Supplier::query()
                ->onlyTrashed()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderByDesc('deleted_at')
                ->paginate($perPage),
            default => throw new \LogicException('Invalid catalogs trash tab.'),
        };
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
