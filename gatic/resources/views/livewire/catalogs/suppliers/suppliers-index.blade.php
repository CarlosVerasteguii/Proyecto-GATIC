<div class="container position-relative catalogs-page catalogs-suppliers-page">
    <x-ui.long-request target="delete" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $suppliers->total();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Proveedores"
                subtitle="Proveedores para compras y mantenimiento."
                filterId="suppliers-filters"
                :filtersCollapsible="false"
                class="catalogs-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Catálogos', 'url' => route('catalogs.suppliers.index')],
                        ['label' => 'Proveedores', 'url' => null],
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

                    <a class="btn btn-sm btn-primary" href="{{ route('catalogs.suppliers.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo proveedor
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <label for="suppliers-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="suppliers-search"
                            name="q"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar proveedor por nombre"
                            autocomplete="off"
                        />

                        @php($clearHidden = ! $hasSearch)
                        <button
                            type="button"
                            class="btn btn-outline-secondary{{ $clearHidden ? ' invisible' : '' }}"
                            wire:click="clearSearch"
                            wire:loading.attr="disabled"
                            wire:target="clearSearch"
                            aria-label="Limpiar búsqueda"
                            title="Limpiar búsqueda"
                            @if ($clearHidden) disabled aria-hidden="true" tabindex="-1" @endif
                        >
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </x-slot:search>

                <div class="small text-body-secondary mb-2" aria-live="polite">
                    <span>Mostrando {{ number_format($resultsCount) }} resultado{{ $resultsCount === 1 ? '' : 's' }}.</span>
                    <span
                        class="ms-2 align-items-center gap-2"
                        wire:loading.inline-flex
                        wire:target="search,clearSearch"
                    >
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        Buscando…
                    </span>
                </div>

                <div
                    class="table-responsive border rounded-3 catalogs-table-wrap"
                    wire:loading.class="opacity-50 pe-none"
                    wire:target="search,clearSearch"
                >
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head catalogs-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Notas</th>
                                <th class="text-end" style="width: 1%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $supplier)
                                <tr wire:key="supplier-row-{{ $supplier->id }}">
                                    <td>
                                        <div class="fw-semibold">{{ $supplier->name }}</div>
                                        <div class="text-body-secondary small">ID {{ $supplier->id }}</div>
                                    </td>
                                    <td>
                                        @if (is_string($supplier->contact) && $supplier->contact !== '')
                                            {{ $supplier->contact }}
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (is_string($supplier->notes) && $supplier->notes !== '')
                                            <div class="text-truncate" style="max-width: 480px;">
                                                {{ $supplier->notes }}
                                            </div>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                class="btn btn-outline-primary catalogs-action-btn"
                                                href="{{ route('catalogs.suppliers.edit', ['supplier' => $supplier->id]) }}"
                                                aria-label="Editar proveedor {{ $supplier->name }}"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger catalogs-action-btn"
                                                wire:click="delete({{ $supplier->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar el proveedor «{{ $supplier->name }}»?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                                aria-label="Eliminar proveedor {{ $supplier->name }}"
                                                title="Eliminar"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
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
                                                icon="bi-truck"
                                                title="No hay proveedores"
                                                description="Crea tu primer proveedor para registrar compras y mantenimiento."
                                                compact
                                            >
                                                <a class="btn btn-sm btn-primary" href="{{ route('catalogs.suppliers.create') }}">
                                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo proveedor
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
                    {{ $suppliers->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>

