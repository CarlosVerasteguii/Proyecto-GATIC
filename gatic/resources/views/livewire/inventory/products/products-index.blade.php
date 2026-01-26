<div class="container position-relative">
    <x-ui.long-request />
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'category', 'brand', 'availability', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.toolbar title="Productos" filterId="products-filters">
                <x-slot:actions>
                    @can('inventory.manage')
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.create') }}">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo producto
                        </a>
                    @endcan
                </x-slot:actions>

                <x-slot:search>
                    <label for="products-search" class="form-label">Buscar</label>
                    <input
                        id="products-search"
                        type="text"
                        class="form-control"
                        placeholder="Buscar por nombre."
                        wire:model.live.debounce.300ms="search"
                    />
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-2">
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
                    <div class="col-12 col-md-2">
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
                        <label for="filter-availability" class="form-label">Disponibilidad</label>
                        <select
                            id="filter-availability"
                            class="form-select"
                            wire:model.live="availability"
                            aria-label="Filtrar por disponibilidad"
                        >
                            <option value="all">Todas</option>
                            <option value="with_available">Con disponibles</option>
                            <option value="without_available">Sin disponibles</option>
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

                <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Disponibles</th>
                                    <th class="text-end">No disponibles</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    @php
                                        $isSerialized = (bool) $product->category?->is_serialized;
                                        $total = $isSerialized ? (int) ($product->assets_total ?? 0) : (int) ($product->qty_total ?? 0);
                                        $unavailable = $isSerialized ? (int) ($product->assets_unavailable ?? 0) : 0;
                                        $available = max($total - $unavailable, 0);
                                    @endphp

                                    <tr @class(['table-warning' => $available === 0])>
                                        <td>
                                            <a
                                                class="text-decoration-none"
                                                href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}"
                                            >
                                                {{ $product->name }}
                                            </a>
                                        </td>
                                        <td>{{ $product->category?->name ?? '-' }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td>{{ $product->category?->is_serialized ? 'Serializado' : 'Por cantidad' }}</td>
                                        <td class="text-end">
                                            {{ $total }}
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold">{{ $available }}</span>
                                            @if ($available === 0)
                                                <span class="badge text-bg-danger ms-2" role="status">Sin disponibles</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ $unavailable }}
                                        </td>
                                        <td class="text-end">
                                            <a
                                                class="btn btn-sm btn-outline-secondary"
                                                href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}"
                                            >
                                                Ver
                                            </a>
                                            @if ($product->category?->is_serialized)
                                                <a
                                                    class="btn btn-sm btn-outline-secondary"
                                                    href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                                                >
                                                    Activos
                                                </a>
                                            @endif
                                            @can('inventory.manage')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.products.edit', ['product' => $product->id]) }}">
                                                    Editar
                                                </a>
                                            @endcan
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
                                                    icon="bi-box-seam"
                                                    title="No hay productos"
                                                    description="Crea tu primer producto para comenzar a gestionar el inventario."
                                                    compact
                                                >
                                                    @can('inventory.manage')
                                                        <a href="{{ route('inventory.products.create') }}" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear producto
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
                    {{ $products->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
