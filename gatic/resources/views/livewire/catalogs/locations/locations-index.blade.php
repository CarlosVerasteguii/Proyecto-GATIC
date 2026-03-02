<div
    class="container position-relative catalogs-page catalogs-locations-page"
    x-data="{}"
    x-on:focus-field.window="
        if ($event.detail.field !== 'location-name') {
            return;
        }
        const el = document.getElementById('location-name');
        if (el) {
            el.focus();
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (el.select) {
                el.select();
            }
        }
    "
>
    <x-ui.long-request target="save,delete,edit,cancelEdit,clearSearch" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $locations->total();
        $isEditingThisPage = (bool) $isEditing;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Ubicaciones"
                subtitle="Ubicaciones físicas para activos y productos."
                filterId="locations-filters"
                :filtersCollapsible="false"
                class="catalogs-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Catálogos', 'url' => route('catalogs.locations.index')],
                        ['label' => 'Ubicaciones', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Total <strong>{{ number_format($summary['total']) }}</strong>
                    </x-ui.badge>
                    @if ($hasSearch)
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Resultados <strong>{{ number_format($summary['results']) }}</strong>
                        </x-ui.badge>
                    @endif
                    @if ($isEditingThisPage)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false" icon="bi-pencil-square">Editando</x-ui.badge>
                    @endif
                </x-slot:actions>

                <x-slot:search>
                    <label for="locations-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="locations-search"
                            name="q"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar ubicación por nombre"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-9">
                        <div @class([
                            'catalogs-inline-editor',
                            'catalogs-inline-editor--editing' => $isEditingThisPage,
                        ])>
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <label for="location-name" class="catalogs-inline-editor__heading">
                                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                        {{ $isEditingThisPage ? 'Editar ubicación' : 'Nueva ubicación' }}
                                    </label>

                                    <div class="catalogs-inline-editor__subtext">
                                        @if ($isEditingThisPage)
                                            Actualiza el nombre de la ubicación seleccionada.
                                        @else
                                            Agrega ubicaciones para usarlas en activos y productos.
                                        @endif
                                    </div>
                                </div>

                                @if ($isEditingThisPage)
                                    <span class="catalogs-inline-editor__meta">
                                        ID {{ $this->locationId }}
                                    </span>
                                @endif
                            </div>

                            <div class="input-group">
                                <span class="input-group-text bg-body">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="location-name"
                                    name="name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Nombre de la ubicación…"
                                    wire:model.defer="name"
                                    wire:keydown.enter.prevent="save"
                                    @if ($isEditingThisPage) wire:keydown.escape.prevent="cancelEdit" @endif
                                    aria-label="Nombre de la ubicación"
                                    aria-describedby="location-name-shortcuts"
                                    maxlength="255"
                                    autocomplete="off"
                                />
                            </div>

                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="catalogs-inline-editor__footer">
                                <div id="location-name-shortcuts" class="catalogs-inline-editor__keys">
                                    <span><kbd>Enter</kbd> Guardar</span>
                                    @if ($isEditingThisPage)
                                        <span><kbd>Esc</kbd> Cancelar</span>
                                    @endif
                                </div>

                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        wire:click="save"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                        aria-label="{{ $isEditingThisPage ? 'Guardar cambios de ubicación' : 'Guardar nueva ubicación' }}"
                                    >
                                        <span wire:loading.remove wire:target="save">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                            {{ $isEditingThisPage ? 'Guardar cambios' : 'Guardar' }}
                                        </span>
                                        <span wire:loading.inline wire:target="save">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                Guardando…
                                            </span>
                                        </span>
                                    </button>

                                    @if ($isEditingThisPage)
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            wire:click="cancelEdit"
                                            wire:loading.attr="disabled"
                                            wire:target="cancelEdit"
                                        >
                                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </div>
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
                            @forelse ($locations as $location)
                                @php($isRowEditing = $isEditingThisPage && $this->locationId === $location->id)
                                <tr wire:key="location-row-{{ $location->id }}" @class(['catalogs-row-editing' => $isRowEditing])>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $location->name }}</div>
                                            <div class="small text-body-secondary">ID {{ $location->id }}</div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            @if ($isRowEditing)
                                                <button type="button" class="btn btn-sm btn-primary" disabled>
                                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                    Editando
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    wire:click="edit({{ $location->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="edit({{ $location->id }})"
                                                    aria-label="Editar ubicación {{ $location->name }}"
                                                >
                                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                    Editar
                                                </button>
                                            @endif
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $location->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar esta ubicación?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete({{ $location->id }})"
                                                aria-label="Eliminar ubicación {{ $location->name }}"
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
                                                icon="bi-geo-alt"
                                                title="No hay ubicaciones"
                                                description="Crea tu primera ubicación para usarla en activos y productos."
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
                    {{ $locations->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
