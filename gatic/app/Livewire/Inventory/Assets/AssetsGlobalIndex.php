<?php

namespace App\Livewire\Inventory\Assets;

use App\Actions\Movements\Assets\BulkAssignAssetsToEmployee;
use App\Actions\Movements\Undo\CreateUndoToken;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\UndoToken;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class AssetsGlobalIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    private const STATUS_ALL = 'all';

    private const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @var array<string, string>
     */
    private const SORT_COLUMNS = [
        'product' => 'products.name',
        'serial' => 'assets.serial',
        'asset_tag' => 'assets.asset_tag',
        'status' => 'assets.status',
        'location' => 'locations.name',
    ];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'location')]
    public ?int $locationId = null;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    #[Url(as: 'status')]
    public string $status = self::STATUS_ALL;

    #[Url(as: 'sort')]
    public string $sort = 'serial';

    #[Url(as: 'dir')]
    public string $direction = 'asc';

    /** @var list<int> */
    public array $selectedAssetIds = [];

    public bool $showBulkAssignModal = false;

    public ?int $bulkEmployeeId = null;

    public string $bulkNote = '';

    #[On('inventory:asset-changed')]
    public function onAssetChanged(int $assetId): void
    {
        Gate::authorize('inventory.view');
    }

    #[On('inventory:assets-batch-changed')]
    public function onAssetsBatchChanged(string $batchUuid, array $assetIds = []): void
    {
        Gate::authorize('inventory.manage');

        $this->selectedAssetIds = [];
        $this->resetErrorBag('selectedAssetIds');
        $this->resetPage();
    }

    public function mount(): void
    {
        Gate::authorize('inventory.view');

        $this->status = $this->normalizeStatus($this->status);
        $this->sort = $this->normalizeSort($this->sort);
        $this->direction = $this->normalizeDirection($this->direction);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedBrandId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->status = $this->normalizeStatus($this->status);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationId', 'categoryId', 'brandId', 'status']);
        $this->status = self::STATUS_ALL;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->locationId !== null
            || $this->categoryId !== null
            || $this->brandId !== null
            || $this->status !== self::STATUS_ALL;
    }

    public function sortBy(string $key): void
    {
        $key = $this->normalizeSort($key);

        if ($this->sort === $key) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $key;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    public function updatedPaginators($page, string $pageName): void
    {
        $this->selectedAssetIds = [];
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function selectAllVisible(array $ids): void
    {
        Gate::authorize('inventory.manage');

        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $ids
        ), static fn (int $id): bool => $id > 0)));

        $this->selectedAssetIds = $normalized;
        $this->resetErrorBag('selectedAssetIds');
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->selectedAssetIds = [];
        $this->resetErrorBag('selectedAssetIds');
    }

    public function openBulkAssignModal(): void
    {
        Gate::authorize('inventory.manage');

        if (count($this->selectedAssetIds) < 1) {
            $this->addError('selectedAssetIds', 'Debe seleccionar al menos un activo.');

            return;
        }

        $this->bulkEmployeeId = null;
        $this->bulkNote = '';
        $this->resetErrorBag();
        $this->showBulkAssignModal = true;
    }

    public function bulkAssign(): void
    {
        Gate::authorize('inventory.manage');

        $maxAssets = (int) config('gatic.inventory.bulk_actions.max_assets', 50);

        $assetIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $this->selectedAssetIds
        ), static fn (int $id): bool => $id > 0)));

        $this->selectedAssetIds = $assetIds;
        $this->resetErrorBag();

        try {
            $this->validate([
                'selectedAssetIds' => ['required', 'array', 'min:1', 'max:'.$maxAssets],
                'selectedAssetIds.*' => ['required', 'integer', 'distinct'],
                'bulkEmployeeId' => ['required', 'integer', Rule::exists('employees', 'id')],
                'bulkNote' => ['required', 'string', 'min:5', 'max:1000'],
            ], [
                'selectedAssetIds.required' => 'Debe seleccionar al menos un activo.',
                'selectedAssetIds.array' => 'La selección de activos es inválida.',
                'selectedAssetIds.min' => 'Debe seleccionar al menos un activo.',
                'selectedAssetIds.max' => "El máximo permitido es {$maxAssets} activos.",
                'bulkEmployeeId.required' => 'Debes seleccionar un empleado.',
                'bulkEmployeeId.exists' => 'El empleado seleccionado no existe.',
                'bulkNote.required' => 'La nota es obligatoria.',
                'bulkNote.min' => 'La nota debe tener al menos :min caracteres.',
                'bulkNote.max' => 'La nota no puede exceder :max caracteres.',
            ]);

            $actorUserId = auth()->id();
            if ($actorUserId === null) {
                abort(403);
            }

            $action = new BulkAssignAssetsToEmployee;
            $result = $action->execute([
                'asset_ids' => $assetIds,
                'employee_id' => $this->bulkEmployeeId,
                'note' => $this->bulkNote,
                'actor_user_id' => (int) $actorUserId,
            ]);

            $count = $result['movements']->count();
            $batchUuid = (string) $result['batch_uuid'];

            $this->selectedAssetIds = [];
            $this->showBulkAssignModal = false;
            $this->bulkEmployeeId = null;
            $this->bulkNote = '';
            $this->resetErrorBag();

            $undoTokenId = null;
            try {
                $undoTokenId = (new CreateUndoToken)->execute([
                    'actor_user_id' => (int) $actorUserId,
                    'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
                    'batch_uuid' => $batchUuid,
                ])->id;
            } catch (Throwable) {
                $undoTokenId = null;
            }

            $actionPayload = null;
            if (is_string($undoTokenId) && $undoTokenId !== '') {
                $actionPayload = [
                    'label' => 'Deshacer',
                    'event' => 'ui:undo-movement',
                    'params' => ['token' => $undoTokenId],
                ];
            }

            $this->toast(
                type: 'success',
                title: 'Asignación masiva',
                message: "Se asignaron {$count} activos.",
                action: $actionPayload,
            );
        } catch (ValidationException $e) {
            $this->mapBulkActionErrors($e);
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $errorId = app(ErrorReporter::class)->report($e, request());
            $this->toastError(
                message: 'Ocurrió un error al asignar los activos.',
                title: 'Error inesperado',
                errorId: $errorId,
            );
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $this->status = $this->normalizeStatus($this->status);
        $this->sort = $this->normalizeSort($this->sort);
        $this->direction = $this->normalizeDirection($this->direction);

        $escapedSearch = trim($this->search) !== '' ? $this->escapeLike(trim($this->search)) : null;

        $locations = Location::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = Asset::query()
            ->select('assets.*')
            ->join('products', function ($join) {
                $join->on('products.id', '=', 'assets.product_id')
                    ->whereNull('products.deleted_at');
            })
            ->leftJoin('locations', function ($join) {
                $join->on('locations.id', '=', 'assets.location_id')
                    ->whereNull('locations.deleted_at');
            })
            ->with(['product.category', 'location', 'currentEmployee'])
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->where(function ($query) use ($escapedSearch) {
                    $query->whereRaw("assets.serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("assets.asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("products.name like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("locations.name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                });
            })
            ->when($this->locationId !== null, function ($query) {
                $query->where('assets.location_id', $this->locationId);
            })
            ->when($this->categoryId !== null, function ($query) {
                $query->where('products.category_id', $this->categoryId);
            })
            ->when($this->brandId !== null, function ($query) {
                $query->where('products.brand_id', $this->brandId);
            });

        if ($this->status === self::STATUS_ALL) {
            $query->where('assets.status', '!=', Asset::STATUS_RETIRED);
        } elseif ($this->status === self::STATUS_UNAVAILABLE) {
            $query->whereIn('assets.status', Asset::UNAVAILABLE_STATUSES);
        } else {
            $query->where('assets.status', $this->status);
        }

        $sortColumn = self::SORT_COLUMNS[$this->sort] ?? self::SORT_COLUMNS['serial'];

        return view('livewire.inventory.assets.assets-global-index', [
            'locations' => $locations,
            'categories' => $categories,
            'brands' => $brands,
            'assetStatuses' => Asset::STATUSES,
            'assets' => $query
                ->orderBy($sortColumn, $this->direction)
                ->orderBy('assets.id')
                ->paginate(config('gatic.ui.pagination.per_page', 15)),
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = trim($status);
        if ($normalized === '') {
            return self::STATUS_ALL;
        }

        $allowed = array_merge([self::STATUS_ALL, self::STATUS_UNAVAILABLE], Asset::STATUSES);

        return in_array($normalized, $allowed, true) ? $normalized : self::STATUS_ALL;
    }

    private function normalizeSort(string $sort): string
    {
        $normalized = trim($sort);
        if ($normalized === '' || ! array_key_exists($normalized, self::SORT_COLUMNS)) {
            return 'serial';
        }

        return $normalized;
    }

    private function normalizeDirection(string $direction): string
    {
        $normalized = strtolower(trim($direction));

        return in_array($normalized, ['asc', 'desc'], true) ? $normalized : 'asc';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function mapBulkActionErrors(ValidationException $e): void
    {
        $mapping = [
            'asset_ids' => 'selectedAssetIds',
            'employee_id' => 'bulkEmployeeId',
            'note' => 'bulkNote',
        ];

        foreach ($e->errors() as $field => $messages) {
            $target = $mapping[$field] ?? $field;

            foreach ($messages as $message) {
                $this->addError($target, $message);
            }
        }
    }
}
