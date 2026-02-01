<div class="container position-relative">
    <x-ui.long-request />
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.toolbar title="Activos" :subtitle="$product?->name ?? ''" filterId="assets-filters">
                <x-slot:breadcrumbs>
                    @php
                        $breadcrumbItems = [
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Productos', 'url' => route('inventory.products.index')],
                        ];

                        if ($product) {
                            $breadcrumbItems[] = [
                                'label' => $product->name,
                                'url' => route('inventory.products.show', ['product' => $product->id]),
                            ];
                        }

                        $breadcrumbItems[] = ['label' => 'Activos', 'url' => null];
                    @endphp

                    <x-ui.breadcrumbs :items="$breadcrumbItems" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    @if ($productIsSerialized)
                        <x-ui.column-manager table="inventory-assets" />
                    @endif
                    @can('inventory.manage')
                        @if ($productIsSerialized)
                            <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.assets.create', ['product' => $product->id]) }}">
                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo activo
                            </a>
                        @endif
                    @endcan
                </x-slot:actions>

                @if ($productIsSerialized)
                    <x-slot:search>
                        <label for="assets-search" class="form-label">Buscar</label>
                        <input
                            id="assets-search"
                            type="text"
                            class="form-control"
                            placeholder="Serial o asset tag."
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
                            <label for="filter-status" class="form-label">Estado</label>
                            <select
                                id="filter-status"
                                class="form-select"
                                wire:model.live="status"
                                aria-label="Filtrar por estado"
                            >
                                <option value="all">Todos (sin Retirado)</option>
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
                @endif

                @if (! $productIsSerialized)
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        No hay activos para productos por cantidad.
                    </div>
                @else
                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0" data-column-table="inventory-assets">
                            <thead>
                                <tr>
                                    <th data-column-key="serial" data-column-required="true">Serial</th>
                                    <th data-column-key="asset_tag">Asset tag</th>
                                    <th data-column-key="status">Estado</th>
                                    <th data-column-key="location">Ubicación</th>
                                    <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assets as $asset)
                                    <tr>
                                        <td>{{ $asset->serial }}</td>
                                        <td>{{ $asset->asset_tag ?? '-' }}</td>
                                        <td><x-ui.status-badge :status="$asset->status" /></td>
                                        <td>{{ $asset->location?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            @php
                                                $returnQuery = array_filter([
                                                    'q' => $search,
                                                    'location' => $locationId,
                                                    'status' => $status !== 'all' ? $status : null,
                                                    'page' => $assets->currentPage(),
                                                ], static fn ($value): bool => $value !== null && $value !== '');
                                            @endphp
                                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                                {{-- Dropdown de acciones rapidas (solo Admin/Editor) --}}
                                                <x-ui.quick-action-dropdown :asset="$asset" :productId="$product->id" />

                                                {{-- Boton Ver (todos los roles) --}}
                                                <a
                                                    class="btn btn-sm btn-outline-secondary"
                                                    href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}"
                                                    style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;"
                                                >
                                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                    Ver
                                                </a>

                                                {{-- Boton Editar (solo Admin/Editor) --}}
                                                @can('inventory.manage')
                                                    <a
                                                        class="btn btn-sm btn-outline-primary"
                                                        href="{{ route('inventory.products.assets.edit', ['product' => $product->id, 'asset' => $asset->id]) }}"
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
                                        <td colspan="5">
                                            @if ($this->hasActiveFilters())
                                                <x-ui.empty-state
                                                    variant="filter"
                                                    compact
                                                />
                                            @else
                                                <x-ui.empty-state
                                                    icon="bi-hdd"
                                                    title="No hay activos"
                                                    description="Registra activos para este producto."
                                                    compact
                                                >
                                                    @can('inventory.manage')
                                                        <a href="{{ route('inventory.products.assets.create', ['product' => $product->id]) }}" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo activo
                                                        </a>
                                                    @endcan
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
                @endif
            </x-ui.toolbar>
        </div>
    </div>
</div>
