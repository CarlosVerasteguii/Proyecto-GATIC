<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.toolbar title="Activos" filterId="assets-global-filters">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Activos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.column-manager table="inventory-assets-global" />
                </x-slot:actions>

                <x-slot:search>
                    <label for="assets-global-search" class="form-label">Buscar</label>
                    <input
                        id="assets-global-search"
                        type="text"
                        class="form-control"
                        placeholder="Serial, asset tag o producto."
                        wire:model.live.debounce.300ms="search"
                    />
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="filter-location" class="form-label">Ubicación</label>
                        <select
                            id="filter-location"
                            class="form-select"
                            wire:model.live="locationId"
                            aria-label="Filtrar por ubicación"
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
                            aria-label="Filtrar por categoría"
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
                            aria-label="Filtrar por marca"
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
                            aria-label="Filtrar por estado"
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
                    @if ($this->hasActiveFilters())
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar todos los filtros"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                @php
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
                @endphp

                <div class="table-responsive-xl">
                    <table class="table table-sm table-striped align-middle mb-0" data-column-table="inventory-assets-global">
                        <thead>
                            <tr>
                                <th data-column-key="product" aria-sort="{{ $ariaSort('product') }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none" wire:click="sortBy('product')">
                                        Producto
                                        <i class="bi {{ $sortIcon('product') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th data-column-key="serial" data-column-required="true" aria-sort="{{ $ariaSort('serial') }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none" wire:click="sortBy('serial')">
                                        Serial
                                        <i class="bi {{ $sortIcon('serial') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th data-column-key="asset_tag" aria-sort="{{ $ariaSort('asset_tag') }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none" wire:click="sortBy('asset_tag')">
                                        Asset tag
                                        <i class="bi {{ $sortIcon('asset_tag') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th data-column-key="status" aria-sort="{{ $ariaSort('status') }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none" wire:click="sortBy('status')">
                                        Estado
                                        <i class="bi {{ $sortIcon('status') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th data-column-key="location" aria-sort="{{ $ariaSort('location') }}">
                                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none" wire:click="sortBy('location')">
                                        Ubicación
                                        <i class="bi {{ $sortIcon('location') }} small ms-1" aria-hidden="true"></i>
                                    </button>
                                </th>
                                <th data-column-key="employee">Empleado</th>
                                <th data-column-key="loan_due_date">Vence</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                <tr>
                                    <td>
                                        <a
                                            href="{{ route('inventory.products.show', ['product' => $asset->product_id]) }}"
                                            class="text-decoration-none fw-medium"
                                        >
                                            {{ $asset->product?->name ?? '—' }}
                                        </a>
                                        @if ($asset->product?->category?->name)
                                            <div class="small text-muted">{{ $asset->product->category->name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $asset->serial }}</td>
                                    <td>{{ $asset->asset_tag ?? '-' }}</td>
                                    <td><x-ui.status-badge :status="$asset->status" /></td>
                                    <td>{{ $asset->location?->name ?? '-' }}</td>
                                    <td>{{ $asset->currentEmployee?->full_name ?? '-' }}</td>
                                    <td>
                                        @if ($asset->loan_due_date)
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                                {{ $asset->loan_due_date->format('d/m/Y') }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end align-items-center">
                                            <x-ui.quick-action-dropdown :asset="$asset" :productId="$asset->product_id" :returnTo="$returnTo" />

                                            <a
                                                class="btn btn-sm btn-outline-secondary"
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id, 'returnTo' => $returnTo]) }}"
                                                style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver
                                            </a>

                                            @can('inventory.manage')
                                                <a
                                                    class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('inventory.products.assets.edit', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                    style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;"
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
                                    <td colspan="8">
                                        @if ($this->hasActiveFilters())
                                            <x-ui.empty-state
                                                variant="filter"
                                                compact
                                            />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-hdd"
                                                title="No hay activos"
                                                description="Registra activos desde un producto serializado."
                                                compact
                                            >
                                                <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-box-seam me-1" aria-hidden="true"></i>Ver productos
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
            </x-ui.toolbar>
        </div>
    </div>
</div>
