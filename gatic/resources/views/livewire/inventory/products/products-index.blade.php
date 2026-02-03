<div class="container position-relative">
    <x-ui.long-request />
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'category', 'brand', 'availability', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
        $canManageInventory = auth()->user()->can('inventory.manage');
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.toolbar title="Productos" filterId="products-filters">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Productos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.column-manager table="inventory-products" />
                    @if ($canManageInventory)
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.create') }}">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo producto
                        </a>
                    @endif
                </x-slot:actions>

                <x-slot:search>
                    <form wire:submit.prevent="applySearch">
                        <label for="products-search" class="form-label">Buscar</label>
                        <div class="input-group">
                            <input
                                id="products-search"
                                type="text"
                                class="form-control"
                                placeholder="Ej: Laptop Dell"
                                wire:model.defer="search"
                                wire:loading.attr="disabled"
                                wire:target="applySearch"
                            />
                            <button
                                type="submit"
                                class="btn btn-outline-primary"
                                wire:loading.attr="disabled"
                                wire:target="applySearch"
                            >
                                <span wire:loading.remove wire:target="applySearch">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                </span>
                                <span wire:loading.inline wire:target="applySearch">
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                </span>
                                <span class="visually-hidden">Buscar</span>
                            </button>
                        </div>
                        @if ($this->showMinCharsMessage)
                            <div class="form-text text-warning">
                                Ingresa al menos {{ $minChars }} caracteres para buscar.
                            </div>
                        @endif
                    </form>
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

                <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0" data-column-table="inventory-products">
                            <thead>
                                <tr>
                                    <th data-column-key="name" data-column-required="true">Nombre</th>
                                    <th data-column-key="category">Categoría</th>
                                    <th data-column-key="brand">Marca</th>
                                    <th data-column-key="type">Tipo</th>
                                    <th data-column-key="total" class="text-end">Total</th>
                                    <th data-column-key="available" class="text-end">Disponibles</th>
                                    <th data-column-key="unavailable" class="text-end">No disponibles</th>
                                    <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    @php
                                        $isSerialized = (bool) $product->category?->is_serialized;
                                        $qtyTotal = $product->qty_total;
                                        $total = $isSerialized ? (int) ($product->assets_total ?? 0) : (int) ($qtyTotal ?? 0);
                                        $unavailable = $isSerialized ? (int) ($product->assets_unavailable ?? 0) : 0;
                                        $available = max($total - $unavailable, 0);
                                        $isLowStock = ! $isSerialized
                                            && $qtyTotal !== null
                                            && $product->low_stock_threshold !== null
                                            && $total <= $product->low_stock_threshold;
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
                                            @elseif ($isLowStock)
                                                <span class="badge text-bg-warning ms-2" role="status">Stock bajo</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ $unavailable }}
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    Acciones
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a
                                                            class="dropdown-item"
                                                            href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}"
                                                        >
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    @if ($product->category?->is_serialized)
                                                        <li>
                                                            <a
                                                                class="dropdown-item"
                                                                href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                                                            >
                                                                Ver activos
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if ($canManageInventory)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a
                                                                class="dropdown-item"
                                                                href="{{ route('inventory.products.edit', ['product' => $product->id]) }}"
                                                            >
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
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
                                                    icon="bi-box-seam"
                                                    title="No hay productos"
                                                description="Crea tu primer producto para comenzar a gestionar el inventario."
                                                    compact
                                                >
                                                    @if ($canManageInventory)
                                                        <a href="{{ route('inventory.products.create') }}" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear producto
                                                        </a>
                                                    @endif
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
