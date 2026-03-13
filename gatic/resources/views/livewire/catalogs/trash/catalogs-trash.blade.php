<div class="container position-relative">
    <x-ui.long-request target="restore, purge, emptyTrash" />

    @php
        $resultsCount = $records->total();
        $hasSearch = trim($search) !== '';
        $currentTabCount = $tabCounts[$tab] ?? 0;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Papelera de catálogos"
                subtitle="Recupera o depura categorías, marcas, ubicaciones y proveedores eliminados con contexto administrativo claro."
                filterId="catalogs-trash-search"
                class="catalogs-trash-toolbar"
                searchColClass="col-12 col-lg-5"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Catálogos', 'url' => route('catalogs.categories.index')],
                        ['label' => 'Papelera', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Total <strong>{{ number_format($totalCount) }}</strong>
                    </x-ui.badge>
                    <x-ui.badge tone="info" variant="compact" :with-rail="false">
                        {{ $currentTab['label'] }} <strong>{{ number_format($currentTabCount) }}</strong>
                    </x-ui.badge>
                    @if ($hasSearch)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Resultados <strong>{{ number_format($resultsCount) }}</strong>
                        </x-ui.badge>
                    @endif

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        wire:click="emptyTrash"
                        wire:confirm="¿Estás seguro de vaciar {{ $currentTab['label'] }}? Esta acción es irreversible."
                        wire:loading.attr="disabled"
                        wire:target="emptyTrash"
                        @disabled($currentTabCount === 0)
                        aria-label="Vaciar {{ $currentTab['label'] }} de la papelera"
                    >
                        <span wire:loading.remove wire:target="emptyTrash">
                            <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Vaciar {{ $currentTab['label'] }}
                        </span>
                        <span wire:loading.inline wire:target="emptyTrash">
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                Procesando…
                            </span>
                        </span>
                    </button>
                </x-slot:actions>

                <x-slot:search>
                    <label for="catalogs-trash-search-input" class="form-label">Buscar en papelera</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="catalogs-trash-search-input"
                            type="search"
                            class="form-control"
                            placeholder="{{ $currentTab['search_placeholder'] }}"
                            wire:model.live.debounce.300ms="search"
                            aria-label="{{ $currentTab['search_placeholder'] }}"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-ui.section-card
                    :title="$currentTab['label']"
                    :subtitle="$hasSearch ? 'Coincidencias del filtro actual.' : 'Elementos eliminados listos para restauración o depuración.'"
                    :icon="$currentTab['icon']"
                >
                    <x-slot:actions>
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Visibles <strong>{{ number_format($resultsCount) }}</strong>
                        </x-ui.badge>
                    </x-slot:actions>

                    <div class="d-flex flex-column gap-3">
                        <div class="row g-2" role="tablist" aria-label="Tipos de catálogos en papelera">
                            @foreach ($tabs as $tabKey => $tabConfig)
                                <div class="col-12 col-sm-6 col-xl-3">
                                    <button
                                        id="catalogs-trash-tab-{{ $tabKey }}"
                                        type="button"
                                        class="btn btn-sm w-100 d-flex align-items-center justify-content-between gap-3 {{ $tab === $tabKey ? 'btn-primary' : 'btn-outline-secondary' }}"
                                        wire:click="setTab('{{ $tabKey }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="setTab"
                                        role="tab"
                                        aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
                                        aria-controls="catalogs-trash-panel"
                                    >
                                        <span class="d-inline-flex align-items-center gap-2 text-start">
                                            <i class="bi {{ $tabConfig['icon'] }}" aria-hidden="true"></i>
                                            <span>{{ $tabConfig['label'] }}</span>
                                        </span>
                                        <span class="badge rounded-pill {{ $tab === $tabKey ? 'text-bg-light' : 'text-bg-secondary' }}">
                                            {{ number_format($tabCounts[$tabKey] ?? 0) }}
                                        </span>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex flex-column gap-2">
                            @if ($hasSearch)
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="small text-body-secondary">Búsqueda activa:</span>
                                    <x-ui.badge tone="info" variant="compact" :with-rail="false">{{ $search }}</x-ui.badge>
                                </div>
                            @endif

                            <div class="small text-body-secondary">
                                {{ $currentTab['description'] }}
                            </div>
                        </div>

                        <div
                            id="catalogs-trash-panel"
                            role="tabpanel"
                            aria-labelledby="catalogs-trash-tab-{{ $tab }}"
                        >
                            <div class="table-responsive-xl border rounded-3">
                                <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                    @if ($tab === 'categories')
                                        <thead>
                                            <tr>
                                                <th>Categoría</th>
                                                <th>Configuración</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $category)
                                                <tr wire:key="catalogs-trash-category-{{ $category->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $category->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $category->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <x-ui.badge :tone="$category->is_serialized ? 'info' : 'neutral'" variant="compact" :with-rail="false">
                                                                {{ $category->is_serialized ? 'Serializado' : 'No serializado' }}
                                                            </x-ui.badge>
                                                            <x-ui.badge :tone="$category->requires_asset_tag ? 'warning' : 'neutral'" variant="compact" :with-rail="false">
                                                                {{ $category->requires_asset_tag ? 'Requiere asset tag' : 'Sin asset tag obligatorio' }}
                                                            </x-ui.badge>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $category->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $category->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('categories', {{ $category->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar esta categoría?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar categoría {{ $category->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('categories', {{ $category->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente esta categoría? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar categoría {{ $category->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
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
                                                                :icon="$currentTab['icon']"
                                                                :title="$currentTab['empty_title']"
                                                                :description="$currentTab['empty_description']"
                                                                compact
                                                            >
                                                                <a href="{{ route($currentTab['empty_route']) }}" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>{{ $currentTab['empty_action'] }}
                                                                </a>
                                                            </x-ui.empty-state>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    @elseif ($tab === 'brands')
                                        <thead>
                                            <tr>
                                                <th>Marca</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $brand)
                                                <tr wire:key="catalogs-trash-brand-{{ $brand->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $brand->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $brand->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $brand->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $brand->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('brands', {{ $brand->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar esta marca?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar marca {{ $brand->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('brands', {{ $brand->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente esta marca? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar marca {{ $brand->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3">
                                                        @if ($hasSearch)
                                                            <x-ui.empty-state variant="filter" compact />
                                                        @else
                                                            <x-ui.empty-state
                                                                :icon="$currentTab['icon']"
                                                                :title="$currentTab['empty_title']"
                                                                :description="$currentTab['empty_description']"
                                                                compact
                                                            >
                                                                <a href="{{ route($currentTab['empty_route']) }}" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>{{ $currentTab['empty_action'] }}
                                                                </a>
                                                            </x-ui.empty-state>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    @elseif ($tab === 'locations')
                                        <thead>
                                            <tr>
                                                <th>Ubicación</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $location)
                                                <tr wire:key="catalogs-trash-location-{{ $location->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $location->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $location->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $location->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $location->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('locations', {{ $location->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar esta ubicación?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar ubicación {{ $location->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('locations', {{ $location->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente esta ubicación? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar ubicación {{ $location->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3">
                                                        @if ($hasSearch)
                                                            <x-ui.empty-state variant="filter" compact />
                                                        @else
                                                            <x-ui.empty-state
                                                                :icon="$currentTab['icon']"
                                                                :title="$currentTab['empty_title']"
                                                                :description="$currentTab['empty_description']"
                                                                compact
                                                            >
                                                                <a href="{{ route($currentTab['empty_route']) }}" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>{{ $currentTab['empty_action'] }}
                                                                </a>
                                                            </x-ui.empty-state>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    @else
                                        <thead>
                                            <tr>
                                                <th>Proveedor</th>
                                                <th>Contacto</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $supplier)
                                                <tr wire:key="catalogs-trash-supplier-{{ $supplier->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $supplier->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $supplier->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $supplier->contact ?: '—' }}</td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $supplier->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $supplier->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('suppliers', {{ $supplier->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar este proveedor?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar proveedor {{ $supplier->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('suppliers', {{ $supplier->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente este proveedor? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar proveedor {{ $supplier->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
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
                                                                :icon="$currentTab['icon']"
                                                                :title="$currentTab['empty_title']"
                                                                :description="$currentTab['empty_description']"
                                                                compact
                                                            >
                                                                <a href="{{ route($currentTab['empty_route']) }}" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>{{ $currentTab['empty_action'] }}
                                                                </a>
                                                            </x-ui.empty-state>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    @endif
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $records->links() }}
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            </x-ui.toolbar>
        </div>
    </div>
</div>
