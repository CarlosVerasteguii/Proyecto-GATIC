<div class="container position-relative catalogs-page catalogs-brands-page">
    <x-ui.long-request target="save,delete" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $brands->total();
        $isEditingThisPage = (bool) $isEditing;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Marcas"
                filterId="brands-filters"
                :filtersCollapsible="false"
                class="catalogs-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Catálogos', 'url' => route('catalogs.brands.index')],
                        ['label' => 'Marcas', 'url' => null],
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
                    @if ($isEditingThisPage)
                        <span class="dash-chip">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            Editando
                        </span>
                    @endif
                </x-slot:actions>

                <x-slot:search>
                    <label for="brands-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="brands-search"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar marca por nombre"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-6">
                        <div class="catalogs-inline-editor">
                            <label for="brand-name" class="form-label">
                                {{ $isEditing ? 'Editar marca' : 'Nueva marca' }}
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-body">
                                    <i class="bi bi-badge-tm" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="brand-name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Nombre de la marca…"
                                    wire:model.defer="name"
                                    wire:keydown.enter.prevent="save"
                                    autocomplete="off"
                                    maxlength="255"
                                />

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    wire:target="save"
                                    aria-label="{{ $isEditing ? 'Guardar cambios de marca' : 'Guardar nueva marca' }}"
                                >
                                    <span wire:loading.remove wire:target="save">Guardar</span>
                                    <span wire:loading.inline wire:target="save">
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                            Guardando…
                                        </span>
                                    </span>
                                </button>

                                @if ($isEditing)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        wire:click="cancelEdit"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelEdit"
                                    >
                                        Cancelar
                                    </button>
                                @endif
                            </div>

                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </x-slot:filters>

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
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($brands as $brand)
                                @php($isRowEditing = $isEditingThisPage && $this->brandId === $brand->id)
                                <tr wire:key="brand-row-{{ $brand->id }}" @class(['catalogs-row-editing' => $isRowEditing])>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $brand->name }}</div>
                                            <div class="small text-body-secondary">ID {{ $brand->id }}</div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="edit({{ $brand->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                                aria-label="Editar marca {{ $brand->name }}"
                                            >
                                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $brand->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar esta marca?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                                aria-label="Eliminar marca {{ $brand->name }}"
                                            >
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        @if ($hasSearch)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-badge-tm"
                                                title="No hay marcas"
                                                description="Crea tu primera marca para usarla en productos y activos."
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
                    {{ $brands->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
