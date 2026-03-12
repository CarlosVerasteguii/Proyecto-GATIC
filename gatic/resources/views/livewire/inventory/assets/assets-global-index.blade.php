<div class="container position-relative">
    <x-ui.long-request />

    @php
        $resultsCount = $assets->total();
        $hasFilters = $this->hasActiveFilters();
        $selectedAssetsCount = count($selectedAssetIds);
        $activeFiltersCount = collect([
            trim($search) !== '' ? 'search' : null,
            $locationId !== null ? 'location' : null,
            $categoryId !== null ? 'category' : null,
            $brandId !== null ? 'brand' : null,
            $status !== 'all' ? 'status' : null,
        ])->filter()->count();

        $selectedLocation = $locations->firstWhere('id', $locationId);
        $selectedCategory = $categories->firstWhere('id', $categoryId);
        $selectedBrand = $brands->firstWhere('id', $brandId);

        $statusLabel = match ($status) {
            'unavailable' => 'No disponibles',
            'all' => 'Todos (sin retirados)',
            default => $status,
        };

        $sortIcon = static function (string $key) use ($sort, $direction): string {
            if ($sort !== $key) {
                return 'bi-arrow-down-up';
            }

            return $direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
        };

        $ariaSort = static function (string $key) use ($sort, $direction): string {
            if ($sort !== $key) {
                return 'none';
            }

            return $direction === 'asc' ? 'ascending' : 'descending';
        };

        $returnToParams = array_filter([
            'q' => $search !== '' ? $search : null,
            'location' => $locationId,
            'category' => $categoryId,
            'brand' => $brandId,
            'status' => $status !== 'all' ? $status : null,
            'sort' => $sort !== 'serial' ? $sort : null,
            'dir' => $direction !== 'asc' ? $direction : null,
            'page' => $assets->currentPage() > 1 ? $assets->currentPage() : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $returnToUrl = route('inventory.assets.index', $returnToParams);
        $returnToPath = parse_url($returnToUrl, PHP_URL_PATH) ?: '/inventory/assets';
        $returnToQuery = parse_url($returnToUrl, PHP_URL_QUERY);
        $returnTo = is_string($returnToQuery) && $returnToQuery !== ''
            ? "{$returnToPath}?{$returnToQuery}"
            : $returnToPath;

        $attentionTone = $summary['overdue_loans'] > 0 ? 'danger' : 'info';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Activos"
                subtitle="Supervisa activos serializados con filtros operativos, lectura rápida del estado actual y acciones por lote."
                filterId="assets-global-filters"
                searchColClass="col-12 col-lg-5"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Activos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Activos <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>

                    @if ($activeFiltersCount > 0)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Filtros <strong>{{ $activeFiltersCount }}</strong>
                        </x-ui.badge>
                    @endif

                    @if ($selectedAssetsCount > 0)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Seleccionados <strong>{{ $selectedAssetsCount }}</strong>
                        </x-ui.badge>
                    @endif

                    <x-ui.column-manager table="inventory-assets-global" />

                    <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i>
                        Productos
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <div>
                        <label for="assets-global-search" class="form-label">Buscar por serial, asset tag o producto</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                id="assets-global-search"
                                type="search"
                                class="form-control"
                                placeholder="Ej: SN-DASH-1-0001 o Laptop Dell…"
                                wire:model.live.debounce.300ms="search"
                                autocomplete="off"
                                spellcheck="false"
                            />
                        </div>
                        <div class="form-text">
                            Filtra activos sin salir del listado y conserva el contexto en la URL.
                        </div>
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="filter-location" class="form-label">Ubicación</label>
                        <select
                            id="filter-location"
                            class="form-select"
                            wire:model.live="locationId"
                            aria-label="Filtrar activos por ubicación"
                        >
                            <option value="">Todas</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="filter-category" class="form-label">Categoría</label>
                        <select
                            id="filter-category"
                            class="form-select"
                            wire:model.live="categoryId"
                            aria-label="Filtrar activos por categoría"
                        >
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="filter-brand" class="form-label">Marca</label>
                        <select
                            id="filter-brand"
                            class="form-select"
                            wire:model.live="brandId"
                            aria-label="Filtrar activos por marca"
                        >
                            <option value="">Todas</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="filter-status" class="form-label">Estado</label>
                        <select
                            id="filter-status"
                            class="form-select"
                            wire:model.live="status"
                            aria-label="Filtrar activos por estado"
                        >
                            <option value="all">Todos (sin Retirado)</option>
                            <option value="unavailable">No disponibles</option>
                            @foreach ($assetStatuses as $assetStatus)
                                <option value="{{ $assetStatus }}">{{ $assetStatus }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar filtros de activos"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="row g-3 mb-4" aria-live="polite">
                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Activos visibles"
                            :value="number_format($summary['total'])"
                            description="Resultado actual con filtros y exclusión default de retirados."
                            icon="bi-hdd-stack"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Disponibles"
                            :value="number_format($summary['available'])"
                            description="Listos para asignar o prestar."
                            variant="success"
                            icon="bi-check2-circle"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="No disponibles"
                            :value="number_format($summary['unavailable'])"
                            description="Asignados, prestados o pendientes de retiro."
                            variant="warning"
                            icon="bi-person-workspace"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Préstamos críticos"
                            :value="number_format($summary['loans_attention'])"
                            description="Vencidos o dentro de la ventana de {{ $loanWindowDays }} días."
                            :variant="$attentionTone"
                            icon="bi-alarm"
                        >
                            <div class="small text-body-secondary mt-2">
                                {{ number_format($summary['overdue_loans']) }} vencidos · {{ number_format($summary['due_soon_loans']) }} por vencer
                            </div>
                        </x-ui.kpi-card>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3 mb-3">
                    @if ($hasFilters)
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-body-secondary">Filtros activos:</span>

                            @if (trim($search) !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Búsqueda: {{ $search }}
                                </x-ui.badge>
                            @endif

                            @if (is_string($selectedLocation?->name) && $selectedLocation->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Ubicación: {{ $selectedLocation->name }}
                                </x-ui.badge>
                            @endif

                            @if (is_string($selectedCategory?->name) && $selectedCategory->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Categoría: {{ $selectedCategory->name }}
                                </x-ui.badge>
                            @endif

                            @if (is_string($selectedBrand?->name) && $selectedBrand->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Marca: {{ $selectedBrand->name }}
                                </x-ui.badge>
                            @endif

                            @if ($status !== 'all')
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">
                                    Estado: {{ $statusLabel }}
                                </x-ui.badge>
                            @endif
                        </div>
                    @endif

                    <div class="small text-body-secondary">
                        Detecta disponibilidad, responsable actual y vencimientos de préstamo antes de entrar al detalle.
                    </div>
                </div>

                @can('inventory.manage')
                    @if ($selectedAssetsCount > 0)
                        <div
                            class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"
                            data-testid="assets-bulk-bar"
                            role="status"
                            aria-live="polite"
                        >
                            <div class="small text-muted">
                                Seleccionados: <strong>{{ $selectedAssetsCount }}</strong>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    wire:click="selectAllVisible(@json($assets->pluck('id')->values()))"
                                    data-testid="assets-select-all-visible"
                                >
                                    Seleccionar todos (página)
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    wire:click="clearSelection"
                                    data-testid="assets-clear-selection"
                                >
                                    Limpiar selección
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    wire:click="openBulkAssignModal"
                                    data-testid="assets-bulk-assign-open"
                                >
                                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                                    Asignar por lote
                                </button>
                            </div>
                        </div>
                    @endif
                @endcan

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} activo{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head" data-column-table="inventory-assets-global">
                        <thead>
                            <tr>
                                @can('inventory.manage')
                                    <th scope="col" data-column-key="select" data-column-required="true" style="width: 44px;">
                                        <span class="visually-hidden">Seleccionar</span>
                                    </th>
                                @endcan
                                <th scope="col" data-column-key="product" aria-sort="{{ $ariaSort('product') }}">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-reset text-decoration-none"
                                        wire:click="sortBy('product')"
                                        aria-label="Ordenar por producto"
                                    >
                                        Producto
                                        <i class="bi {{ $sortIcon('product') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" data-column-key="serial" data-column-required="true" aria-sort="{{ $ariaSort('serial') }}">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-reset text-decoration-none"
                                        wire:click="sortBy('serial')"
                                        aria-label="Ordenar por serial"
                                    >
                                        Serial
                                        <i class="bi {{ $sortIcon('serial') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" data-column-key="asset_tag" aria-sort="{{ $ariaSort('asset_tag') }}">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-reset text-decoration-none"
                                        wire:click="sortBy('asset_tag')"
                                        aria-label="Ordenar por asset tag"
                                    >
                                        Asset tag
                                        <i class="bi {{ $sortIcon('asset_tag') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" data-column-key="status" aria-sort="{{ $ariaSort('status') }}">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-reset text-decoration-none"
                                        wire:click="sortBy('status')"
                                        aria-label="Ordenar por estado"
                                    >
                                        Estado
                                        <i class="bi {{ $sortIcon('status') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" data-column-key="location" aria-sort="{{ $ariaSort('location') }}">
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 text-reset text-decoration-none"
                                        wire:click="sortBy('location')"
                                        aria-label="Ordenar por ubicación"
                                    >
                                        Ubicación
                                        <i class="bi {{ $sortIcon('location') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th scope="col" data-column-key="employee">Responsable</th>
                                <th scope="col" data-column-key="loan_due_date">Vencimiento</th>
                                <th scope="col" data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                @php
                                    $employeeName = $asset->currentEmployee?->name;
                                    $employeeRpe = $asset->currentEmployee?->rpe;
                                    $loanDueDate = $asset->loan_due_date;
                                    $loanBadge = null;

                                    if ($loanDueDate) {
                                        $today = \Illuminate\Support\Carbon::today();

                                        if ($loanDueDate->lt($today)) {
                                            $loanBadge = ['label' => 'Vencido', 'tone' => 'danger'];
                                        } elseif ($loanDueDate->lte($today->copy()->addDays($loanWindowDays))) {
                                            $loanBadge = ['label' => 'Por vencer', 'tone' => 'warning'];
                                        } else {
                                            $loanBadge = ['label' => 'En tiempo', 'tone' => 'success'];
                                        }
                                    }
                                @endphp
                                <tr wire:key="assets-global-{{ $asset->id }}">
                                    @can('inventory.manage')
                                        <td>
                                            <div class="form-check m-0">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="{{ $asset->id }}"
                                                    wire:model.live="selectedAssetIds"
                                                    data-testid="assets-row-checkbox"
                                                    aria-label="Seleccionar activo {{ $asset->serial }}"
                                                >
                                            </div>
                                        </td>
                                    @endcan
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <a
                                                href="{{ route('inventory.products.show', ['product' => $asset->product_id]) }}"
                                                class="text-decoration-none fw-semibold d-inline-block text-truncate mw-100"
                                            >
                                                {{ $asset->product?->name ?? 'Producto no disponible' }}
                                            </a>
                                            <div class="small text-body-secondary text-truncate">
                                                {{ $asset->product?->category?->name ?? 'Sin categoría' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">{{ $asset->serial }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div>{{ $asset->asset_tag ?? '—' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <x-ui.status-badge :status="$asset->status" />
                                    </td>
                                    <td class="min-w-0">
                                        <div class="text-truncate">{{ $asset->location?->name ?? 'Sin ubicación' }}</div>
                                    </td>
                                    <td class="min-w-0">
                                        @if ($asset->currentEmployee)
                                            <a
                                                href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}"
                                                class="text-decoration-none d-inline-flex flex-column min-w-0"
                                            >
                                                <span class="fw-semibold text-truncate">{{ $employeeRpe }}</span>
                                                <span class="small text-body-secondary text-truncate">{{ $employeeName }}</span>
                                            </a>
                                        @else
                                            <span class="text-body-secondary">Sin responsable</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($loanDueDate)
                                            <div class="fw-semibold">{{ $loanDueDate->format('d/m/Y') }}</div>
                                            @if ($loanBadge)
                                                <div class="mt-1">
                                                    <x-ui.badge :tone="$loanBadge['tone']" variant="compact" :with-rail="false">
                                                        {{ $loanBadge['label'] }}
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-body-secondary">Sin vencimiento</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <x-ui.quick-action-dropdown :asset="$asset" :productId="$asset->product_id" :returnTo="$returnTo" />

                                            <a
                                                class="btn btn-sm btn-outline-secondary"
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id, 'returnTo' => $returnTo]) }}"
                                                aria-label="Ver detalle del activo {{ $asset->serial }}"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver detalle
                                            </a>

                                            @can('inventory.manage')
                                                <a
                                                    class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('inventory.products.assets.edit', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                    aria-label="Editar activo {{ $asset->serial }}"
                                                >
                                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                    Editar
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    @can('inventory.manage')
                                        <td colspan="9">
                                    @else
                                        <td colspan="8">
                                    @endcan
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-hdd"
                                                title="No hay activos"
                                                description="Registra activos desde un producto serializado para empezar a operar el inventario."
                                                compact
                                            >
                                                <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-box-seam me-1" aria-hidden="true"></i>
                                                    Ver productos
                                                </a>
                                            </x-ui.empty-state>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $assets->links() }}
                </div>

                @can('inventory.manage')
                    @if ($showBulkAssignModal)
                        <div
                            class="modal fade show d-block"
                            tabindex="-1"
                            style="background: rgba(0,0,0,0.5);"
                            data-testid="assets-bulk-assign-modal"
                        >
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title h5 mb-0">Asignar por lote</h2>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            wire:click="$set('showBulkAssignModal', false)"
                                            aria-label="Cerrar"
                                        ></button>
                                    </div>

                                    <form wire:submit="bulkAssign">
                                        <div class="modal-body">
                                            @error('selectedAssetIds')
                                                <div class="alert alert-danger py-2" role="alert">
                                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                            <div class="mb-3">
                                                <label class="form-label">
                                                    Empleado <span class="text-danger">*</span>
                                                </label>
                                                <livewire:ui.employee-combobox wire:model.live="bulkEmployeeId" />
                                                @error('bulkEmployeeId')
                                                    <div class="invalid-feedback d-block mt-1">
                                                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="mb-0">
                                                <label for="assets-bulk-note" class="form-label">
                                                    Nota <span class="text-danger">*</span>
                                                </label>
                                                <textarea
                                                    id="assets-bulk-note"
                                                    class="form-control @error('bulkNote') is-invalid @enderror"
                                                    wire:model.live="bulkNote"
                                                    rows="3"
                                                    placeholder="Motivo de la asignación (mínimo 5 caracteres)…"
                                                    maxlength="1000"
                                                ></textarea>
                                                @error('bulkNote')
                                                    <div class="invalid-feedback">
                                                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                wire:click="$set('showBulkAssignModal', false)"
                                            >
                                                Cancelar
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                wire:loading.attr="disabled"
                                                wire:target="bulkAssign"
                                            >
                                                <span wire:loading.remove wire:target="bulkAssign">Asignar</span>
                                                <span wire:loading.inline wire:target="bulkAssign">
                                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                                    Asignando…
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
            </x-ui.toolbar>
        </div>
    </div>
</div>
