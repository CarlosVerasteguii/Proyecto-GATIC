<div class="container position-relative catalogs-page catalogs-categories-page">
    <x-ui.long-request target="delete" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $categories->total();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Categorías"
                filterId="categories-filters"
                :filtersCollapsible="false"
                class="catalogs-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Catálogos', 'url' => route('catalogs.categories.index')],
                        ['label' => 'Categorías', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <span class="dash-chip">
                        Total <strong>{{ number_format($summary['total']) }}</strong>
                    </span>
                    @if ($hasSearch)
                        <span class="dash-chip">
                            Resultados <strong>{{ number_format($summary['results']) }}</strong>
                        </span>
                    @endif

                    <a class="btn btn-sm btn-primary" href="{{ route('catalogs.categories.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva categoría
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <label for="categories-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="categories-search"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar categoría por nombre"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-slot:clearFilters>
                    @if ($hasSearch)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearSearch"
                            aria-label="Limpiar búsqueda"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} resultado{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive border rounded-3 catalogs-table-wrap">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head catalogs-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th class="text-center">Serializado</th>
                                <th class="text-center">Requiere asset tag</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr wire:key="category-row-{{ $category->id }}">
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $category->name }}</div>
                                            <div class="small text-body-secondary">ID {{ $category->id }}</div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($category->is_serialized)
                                            <span class="catalogs-indicator catalogs-indicator--yes">
                                                <span class="catalogs-indicator-icon" aria-hidden="true">
                                                    <i class="bi bi-check-lg"></i>
                                                </span>
                                                <span>Sí</span>
                                            </span>
                                        @else
                                            <span class="catalogs-indicator catalogs-indicator--no">
                                                <span class="catalogs-indicator-icon" aria-hidden="true">
                                                    <i class="bi bi-dash-lg"></i>
                                                </span>
                                                <span>No</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($category->requires_asset_tag)
                                            <span class="catalogs-indicator catalogs-indicator--yes">
                                                <span class="catalogs-indicator-icon" aria-hidden="true">
                                                    <i class="bi bi-check-lg"></i>
                                                </span>
                                                <span>Sí</span>
                                            </span>
                                        @else
                                            <span class="catalogs-indicator catalogs-indicator--no">
                                                <span class="catalogs-indicator-icon" aria-hidden="true">
                                                    <i class="bi bi-dash-lg"></i>
                                                </span>
                                                <span>No</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                class="btn btn-sm btn-outline-primary"
                                                href="{{ route('catalogs.categories.edit', ['category' => $category->id]) }}"
                                                aria-label="Editar categoría {{ $category->name }}"
                                            >
                                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                Editar
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $category->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar esta categoría?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                                aria-label="Eliminar categoría {{ $category->name }}"
                                            >
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        @if ($hasSearch)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-folder"
                                                title="No hay categorías"
                                                description="Crea tu primera categoría para organizar productos y activos."
                                                compact
                                            >
                                                <a class="btn btn-sm btn-primary" href="{{ route('catalogs.categories.create') }}">
                                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva categoría
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
                    {{ $categories->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
