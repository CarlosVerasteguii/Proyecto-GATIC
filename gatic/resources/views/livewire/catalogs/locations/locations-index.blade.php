<div class="container position-relative catalogs-page catalogs-locations-page">
    <x-ui.long-request target="save,delete,edit,cancelEdit,clearSearch" />

    @php
        $hasSearch = trim($this->search) !== '';
        $isEditingThisPage = (bool) $isEditing;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Ubicaciones"
                filterId="locations-filters"
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
                    <label for="locations-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="locations-search"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar ubicación por nombre"
                            autocomplete="off"
                        />
                        @if ($hasSearch)
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                wire:click="clearSearch"
                                wire:loading.attr="disabled"
                                wire:target="clearSearch"
                                aria-label="Limpiar búsqueda"
                                title="Limpiar búsqueda"
                            >
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-6">
                        <div class="catalogs-inline-editor">
                            <label for="location-name" class="form-label">
                                {{ $isEditingThisPage ? 'Editar ubicación' : 'Nueva ubicación' }}
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-body">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="location-name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Nombre de la ubicación…"
                                    wire:model.defer="name"
                                    wire:keydown.enter.prevent="save"
                                    @if ($isEditingThisPage) wire:keydown.escape.prevent="cancelEdit" @endif
                                    aria-label="Nombre de la ubicación"
                                    maxlength="255"
                                    autocomplete="off"
                                />

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    wire:target="save"
                                >
                                    <span wire:loading.remove wire:target="save">
                                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                        Guardar
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
                                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                        Cancelar
                                    </button>
                                @endif
                            </div>

                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                Tip: Enter para guardar @if($isEditingThisPage) y Esc para cancelar @endif.
                            </div>
                        </div>
                    </div>
                </x-slot:filters>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($locations->count()) }} de {{ number_format($locations->total()) }}.
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 catalogs-table">
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
