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
                title="Papelera administrativa"
                subtitle="Restaura o depura productos, activos y empleados eliminados sin salir del backoffice."
                filterId="admin-trash-search"
                class="admin-trash-toolbar"
                searchColClass="col-12 col-lg-5"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Administración', 'url' => route('admin.users.index')],
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
                    <label for="admin-trash-search-input" class="form-label">Buscar en papelera</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="admin-trash-search-input"
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
                    :subtitle="$hasSearch ? 'Coincidencias del filtro actual.' : 'Registros eliminados disponibles para restauración o purga.'"
                    :icon="$currentTab['icon']"
                >
                    <x-slot:actions>
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Visibles <strong>{{ number_format($resultsCount) }}</strong>
                        </x-ui.badge>
                    </x-slot:actions>

                    <div class="d-flex flex-column gap-3">
                        <div class="row g-2" role="tablist" aria-label="Tipos de registros en papelera administrativa">
                            @foreach ($tabs as $tabKey => $tabConfig)
                                <div class="col-12 col-sm-6 col-xl-4">
                                    <button
                                        id="admin-trash-tab-{{ $tabKey }}"
                                        type="button"
                                        class="btn btn-sm w-100 d-flex align-items-center justify-content-between gap-3 {{ $tab === $tabKey ? 'btn-primary' : 'btn-outline-secondary' }}"
                                        wire:click="setTab('{{ $tabKey }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="setTab"
                                        role="tab"
                                        aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
                                        aria-controls="admin-trash-panel"
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
                            id="admin-trash-panel"
                            role="tabpanel"
                            aria-labelledby="admin-trash-tab-{{ $tab }}"
                        >
                            <div class="table-responsive-xl border rounded-3">
                                <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                    @if ($tab === 'products')
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th>Marca</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $product)
                                                <tr wire:key="admin-trash-product-{{ $product->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $product->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                                    <td>{{ $product->brand?->name ?? '—' }}</td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $product->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $product->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('products', {{ $product->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar este producto?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar producto {{ $product->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('products', {{ $product->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente este producto? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar producto {{ $product->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
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
                                    @elseif ($tab === 'assets')
                                        <thead>
                                            <tr>
                                                <th>Activo</th>
                                                <th>Producto</th>
                                                <th>Estado</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $asset)
                                                <tr wire:key="admin-trash-asset-{{ $asset->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $asset->serial }}</div>
                                                            <div class="small text-body-secondary text-truncate">
                                                                {{ $asset->asset_tag ? 'Asset tag: '.$asset->asset_tag : 'Sin asset tag' }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="text-truncate">{{ $asset->product?->name ?? '—' }}</div>
                                                            <div class="small text-body-secondary text-truncate">
                                                                {{ $asset->location?->name ? 'Ubicación: '.$asset->location->name : 'Sin ubicación visible' }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <x-ui.status-badge :status="$asset->status" />
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $asset->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $asset->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('assets', {{ $asset->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar este activo?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar activo {{ $asset->serial }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('assets', {{ $asset->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente este activo? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar activo {{ $asset->serial }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
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
                                                <th>RPE</th>
                                                <th>Empleado</th>
                                                <th>Departamento</th>
                                                <th>Eliminado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($records as $employee)
                                                <tr wire:key="admin-trash-employee-{{ $employee->id }}">
                                                    <td class="text-nowrap">
                                                        <span class="fw-semibold">{{ $employee->rpe }}</span>
                                                    </td>
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $employee->name }}</div>
                                                            <div class="small text-body-secondary">ID {{ $employee->id }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $employee->department ?? '—' }}</td>
                                                    <td class="text-nowrap">
                                                        <div>{{ $employee->deleted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                                        <div class="small text-body-secondary">{{ $employee->deleted_at?->diffForHumans() ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="restore('employees', {{ $employee->id }})"
                                                                wire:confirm="¿Confirmas que deseas restaurar este empleado?"
                                                                wire:loading.attr="disabled"
                                                                wire:target="restore"
                                                                aria-label="Restaurar empleado {{ $employee->name }}"
                                                            >
                                                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restaurar
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="purge('employees', {{ $employee->id }})"
                                                                wire:confirm="¿Estás seguro de eliminar permanentemente este empleado? Esta acción es irreversible."
                                                                wire:loading.attr="disabled"
                                                                wire:target="purge"
                                                                aria-label="Purgar empleado {{ $employee->name }}"
                                                            >
                                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Purgar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
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
