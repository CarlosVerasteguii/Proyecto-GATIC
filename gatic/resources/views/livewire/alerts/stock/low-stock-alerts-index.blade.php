<div class="container position-relative">
    <x-ui.long-request />

    @php
        $resultsCount = $alerts->total();
        $hasFilters = $this->hasActiveFilters();
        $selectedCategory = $categories->firstWhere('id', $this->categoryId);
        $selectedBrand = $brands->firstWhere('id', $this->brandId);
        $activeFiltersCount = collect([$this->categoryId, $this->brandId])
            ->filter(static fn ($value): bool => $value !== null && $value !== '')
            ->count();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Alertas de stock bajo"
                subtitle="Identifica productos por cantidad que ya están en o por debajo del umbral configurado."
                filterId="stock-alerts-filters"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Dashboard', 'url' => route('dashboard')],
                        ['label' => 'Alertas de stock bajo', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                        En alerta <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>
                    @if ($activeFiltersCount > 0)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Filtros <strong>{{ $activeFiltersCount }}</strong>
                        </x-ui.badge>
                    @endif

                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Dashboard
                    </a>
                </x-slot:actions>

                <x-slot:filters>
                    <div class="col-12 col-md-4">
                        <label for="stock-filter-category" class="form-label">Categoría</label>
                        <select
                            id="stock-filter-category"
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

                    <div class="col-12 col-md-4">
                        <label for="stock-filter-brand" class="form-label">Marca</label>
                        <select
                            id="stock-filter-brand"
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
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            wire:loading.attr="disabled"
                            wire:target="clearFilters"
                            aria-label="Limpiar filtros de alertas de stock"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="d-flex flex-column gap-3 mb-3">
                    @if ($hasFilters)
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-body-secondary">Filtros activos:</span>
                            @if (is_string($selectedCategory?->name) && $selectedCategory->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Categoría: {{ $selectedCategory->name }}</x-ui.badge>
                            @endif
                            @if (is_string($selectedBrand?->name) && $selectedBrand->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Marca: {{ $selectedBrand->name }}</x-ui.badge>
                            @endif
                        </div>
                    @endif

                    <div class="small text-body-secondary">
                        Revisa primero los productos con menor holgura entre stock actual y umbral para priorizar reposición.
                    </div>
                </div>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} producto{{ $resultsCount === 1 ? '' : 's' }} en alerta.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Marca</th>
                                <th class="text-end">Stock actual</th>
                                <th class="text-end">Umbral</th>
                                <th class="text-end">Brecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alerts as $product)
                                @php
                                    $qtyTotal = (int) ($product->qty_total ?? 0);
                                    $threshold = (int) $product->low_stock_threshold;
                                    $gap = $threshold - $qtyTotal;
                                @endphp
                                <tr>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                            <div class="small text-body-secondary">
                                                {{ $gap > 0 ? 'Debajo del umbral' : 'En el umbral configurado' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                    <td>{{ $product->brand?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-warning">{{ $qtyTotal }}</span>
                                    </td>
                                    <td class="text-end">{{ $threshold }}</td>
                                    <td class="text-end text-nowrap">
                                        <x-ui.badge :tone="$gap > 0 ? 'danger' : 'warning'" variant="compact" :with-rail="false">
                                            {{ $gap > 0 ? "{$gap} abajo" : '0 al límite' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver detalle
                                            </a>

                                            @can('inventory.manage')
                                                <a
                                                    href="{{ route('inventory.products.edit', ['product' => $product->id]) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                    Editar
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-check2-circle"
                                                title="Sin alertas de stock bajo"
                                                description="No hay productos con stock en o por debajo de su umbral configurado."
                                                compact
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $alerts->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
