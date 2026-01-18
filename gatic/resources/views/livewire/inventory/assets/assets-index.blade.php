<div class="container position-relative">
    <x-ui.long-request />
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <span>Activos</span>
                        <small class="text-muted">{{ $product?->name ?? '' }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.index') }}">Volver</a>
                        @can('inventory.manage')
                            @if ($productIsSerialized)
                                <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.assets.create', ['product' => $product->id]) }}">
                                    Nuevo activo
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    @if (! $productIsSerialized)
                        <div class="alert alert-warning mb-0">
                            No hay activos para productos por cantidad.
                        </div>
                    @else
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-12 col-md-3">
                                <label for="assets-search" class="form-label">Buscar</label>
                                <input
                                    id="assets-search"
                                    type="text"
                                    class="form-control"
                                    placeholder="Serial o asset tag."
                                    wire:model.live.debounce.300ms="search"
                                />
                            </div>
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
                            <div class="col-12 col-md-3">
                                @if ($this->hasActiveFilters())
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary w-100"
                                        wire:click="clearFilters"
                                        aria-label="Limpiar todos los filtros"
                                    >
                                        Limpiar
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial</th>
                                        <th>Asset tag</th>
                                        <th>Estado</th>
                                        <th>Ubicación</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assets as $asset)
                                        <tr>
                                            <td>{{ $asset->serial }}</td>
                                            <td>{{ $asset->asset_tag ?? '-' }}</td>
                                            <td>{{ $asset->status }}</td>
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
                                                <a
                                                    class="btn btn-sm btn-outline-secondary"
                                                    href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}"
                                                >
                                                    Ver
                                                </a>
                                                @can('inventory.manage')
                                                    <a
                                                        class="btn btn-sm btn-outline-primary"
                                                        href="{{ route('inventory.products.assets.edit', ['product' => $product->id, 'asset' => $asset->id]) }}"
                                                    >
                                                        Editar
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted">No hay activos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $assets->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
