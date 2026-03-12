<div class="container position-relative">
    <x-ui.long-request />

    @php
        $returnQuery = array_filter(
            request()->only(['q', 'category', 'brand', 'availability', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
        $canManageInventory = auth()->user()->can('inventory.manage');
        $hasFilters = $this->hasActiveFilters();
        $activeFiltersCount = collect([
            trim($search) !== '' ? 'search' : null,
            $categoryId !== null ? 'category' : null,
            $brandId !== null ? 'brand' : null,
            $availability !== 'all' ? 'availability' : null,
        ])->filter()->count();
        $selectedCategory = $categories->firstWhere('id', $categoryId);
        $selectedBrand = $brands->firstWhere('id', $brandId);
        $availabilityLabel = match ($availability) {
            'with_available' => 'Con disponibles',
            'without_available' => 'Sin disponibles',
            default => 'Todas',
        };
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Productos"
                subtitle="Supervisa disponibilidad, tipo de inventario y rutas operativas sin salir del listado."
                filterId="products-filters"
                searchColClass="col-12 col-lg-5"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Productos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Productos <strong>{{ number_format($summary['total_products']) }}</strong>
                    </x-ui.badge>

                    @if ($activeFiltersCount > 0)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Filtros <strong>{{ $activeFiltersCount }}</strong>
                        </x-ui.badge>
                    @endif

                    @if ($summary['low_stock'] > 0)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Stock bajo <strong>{{ number_format($summary['low_stock']) }}</strong>
                        </x-ui.badge>
                    @endif

                    <x-ui.column-manager table="inventory-products" />

                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.assets.index') }}">
                        <i class="bi bi-hdd-stack me-1" aria-hidden="true"></i>
                        Activos
                    </a>

                    @if ($canManageInventory)
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.create') }}">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                            Nuevo producto
                        </a>
                    @endif
                </x-slot:actions>

                <x-slot:search>
                    <form wire:submit.prevent="applySearch">
                        <label for="products-search" class="form-label">Buscar por nombre de producto</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                id="products-search"
                                name="q"
                                type="search"
                                class="form-control"
                                placeholder="Ej: Laptop Dell…"
                                wire:model.defer="search"
                                wire:loading.attr="disabled"
                                wire:target="applySearch"
                                autocomplete="off"
                                spellcheck="false"
                            />
                            <button
                                type="submit"
                                class="btn btn-outline-primary"
                                wire:loading.attr="disabled"
                                wire:target="applySearch"
                            >
                                <span wire:loading.remove wire:target="applySearch">
                                    Buscar
                                </span>
                                <span wire:loading.inline wire:target="applySearch">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Buscando…
                                </span>
                            </button>
                        </div>
                        <div class="form-text">
                            Conserva el estado del listado en la URL y evita búsquedas accidentales demasiado cortas.
                        </div>
                        @if ($this->showMinCharsMessage)
                            <div class="form-text text-warning">
                                Ingresa al menos {{ $minChars }} caracteres para buscar.
                            </div>
                        @endif
                    </form>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="filter-category" class="form-label">Categoría</label>
                        <select
                            id="filter-category"
                            class="form-select"
                            wire:model.live="categoryId"
                            aria-label="Filtrar productos por categoría"
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
                            aria-label="Filtrar productos por marca"
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
                            aria-label="Filtrar productos por disponibilidad"
                        >
                            <option value="all">Todas</option>
                            <option value="with_available">Con disponibles</option>
                            <option value="without_available">Sin disponibles</option>
                        </select>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar filtros de productos"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="row g-3 mb-4" aria-live="polite">
                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Productos visibles"
                            :value="number_format($summary['total_products'])"
                            description="Resultado actual después de búsqueda y filtros."
                            icon="bi-box-seam"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Con disponibles"
                            :value="number_format($summary['with_available'])"
                            description="Productos con stock o activos listos para operar."
                            variant="success"
                            icon="bi-check2-circle"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Sin disponibles"
                            :value="number_format($summary['without_available'])"
                            description="Productos que requieren reposición o revisión."
                            variant="warning"
                            icon="bi-exclamation-circle"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-ui.kpi-card
                            label="Stock bajo"
                            :value="number_format($summary['low_stock'])"
                            description="Aplica a productos por cantidad con umbral configurado."
                            :variant="$summary['low_stock'] > 0 ? 'warning' : 'info'"
                            icon="bi-activity"
                        />
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

                            @if ($availability !== 'all')
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">
                                    Disponibilidad: {{ $availabilityLabel }}
                                </x-ui.badge>
                            @endif
                        </div>
                    @endif

                    <div class="small text-body-secondary">
                        Detecta si el producto opera por activos o por cantidad y entra al flujo correcto desde la misma fila.
                    </div>
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head" data-column-table="inventory-products">
                        <thead>
                            <tr>
                                <th scope="col" data-column-key="name" data-column-required="true">Producto</th>
                                <th scope="col" data-column-key="category">Categoría</th>
                                <th scope="col" data-column-key="supplier">Proveedor</th>
                                <th scope="col" data-column-key="type">Tipo</th>
                                <th scope="col" data-column-key="total" class="text-end">Total</th>
                                <th scope="col" data-column-key="available" class="text-end">Disponibles</th>
                                <th scope="col" data-column-key="unavailable" class="text-end">No disponibles</th>
                                <th scope="col" data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
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

                                <tr
                                    wire:key="inventory-product-{{ $product->id }}"
                                    @class(['table-warning' => $available === 0])
                                >
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <a
                                                class="fw-semibold d-inline-block text-decoration-none text-truncate mw-100"
                                                href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}"
                                            >
                                                {{ $product->name }}
                                            </a>
                                            <div class="small text-body-secondary text-truncate">
                                                {{ collect([$product->brand?->name, $product->supplier?->name])->filter()->implode(' · ') ?: 'Sin marca o proveedor visible' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">{{ $product->category?->name ?? '-' }}</td>
                                    <td class="min-w-0">
                                        <div class="text-truncate">{{ $product->supplier?->name ?? 'Sin proveedor' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <x-ui.badge :tone="$isSerialized ? 'info' : 'warning'" variant="compact" :with-rail="false">
                                            {{ $isSerialized ? 'Serializado' : 'Por cantidad' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="text-end text-nowrap">{{ $total }}</td>
                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold">{{ $available }}</div>
                                        <div class="mt-1">
                                            @if ($available === 0)
                                                <x-ui.badge tone="danger" variant="compact" :with-rail="false" role="status">
                                                    Sin disponibles
                                                </x-ui.badge>
                                            @elseif ($isLowStock)
                                                <x-ui.badge tone="warning" variant="compact" :with-rail="false" role="status">
                                                    Stock bajo
                                                </x-ui.badge>
                                            @else
                                                <x-ui.badge tone="success" variant="compact" :with-rail="false" role="status">
                                                    Operativo
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div>{{ $unavailable }}</div>
                                        <div class="small text-body-secondary">
                                            {{ $isSerialized ? 'Asignados, prestados o pendientes' : 'No aplica' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                class="btn btn-sm btn-outline-secondary"
                                                href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver detalle
                                            </a>

                                            <div class="dropdown">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    Más
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($isSerialized)
                                                        <li>
                                                            <a
                                                                class="dropdown-item"
                                                                href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                                                            >
                                                                Ver activos
                                                            </a>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <a
                                                                class="dropdown-item"
                                                                href="{{ route('inventory.products.kardex', ['product' => $product->id] + $returnQuery) }}"
                                                            >
                                                                Ver kardex
                                                            </a>
                                                        </li>
                                                        @if ($canManageInventory)
                                                            <li>
                                                                <a
                                                                    class="dropdown-item"
                                                                    href="{{ route('inventory.products.movements.quantity', ['product' => $product->id]) }}"
                                                                >
                                                                    Registrar movimiento
                                                                </a>
                                                            </li>
                                                        @endif
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
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-box-seam"
                                                title="No hay productos"
                                                description="Crea tu primer producto para empezar a gestionar inventario por activos o por cantidad."
                                                compact
                                            >
                                                @if ($canManageInventory)
                                                    <a href="{{ route('inventory.products.create') }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                                                        Crear producto
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
