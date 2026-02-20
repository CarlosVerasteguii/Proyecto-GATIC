<div
    class="container position-relative catalogs-page catalogs-suppliers-page"
    x-data="{}"
    x-on:focus-field.window="
        if ($event.detail.field !== 'supplier-name') {
            return;
        }
        const el = document.getElementById('supplier-name');
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
        $resultsCount = $suppliers->total();
        $isEditingThisPage = (bool) $isEditing;
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
                    @if ($isEditingThisPage)
                        <span class="dash-chip">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            Editando
                        </span>
                    @endif
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
                                    <label for="supplier-name" class="catalogs-inline-editor__heading">
                                        <i class="bi bi-truck" aria-hidden="true"></i>
                                        {{ $isEditingThisPage ? 'Editar proveedor' : 'Nuevo proveedor' }}
                                    </label>

                                    <div class="catalogs-inline-editor__subtext">
                                        @if ($isEditingThisPage)
                                            Actualiza los datos del proveedor seleccionado.
                                        @else
                                            Agrega proveedores para usarlos en compras y contratos.
                                        @endif
                                    </div>
                                </div>

                                @if ($isEditingThisPage)
                                    <span class="catalogs-inline-editor__meta">
                                        ID {{ $this->supplierId }}
                                    </span>
                                @endif
                            </div>

                            <div class="row g-2">
                                <div class="col-12 col-lg-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-body">
                                            <i class="bi bi-building" aria-hidden="true"></i>
                                        </span>
                                        <input
                                            id="supplier-name"
                                            name="name"
                                            type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="Nombre del proveedor…"
                                            wire:model.defer="name"
                                            wire:keydown.enter.prevent="save"
                                            @if ($isEditingThisPage) wire:keydown.escape.prevent="cancelEdit" @endif
                                            autocomplete="off"
                                            maxlength="255"
                                            aria-label="Nombre del proveedor"
                                            aria-describedby="supplier-shortcuts"
                                        />
                                    </div>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-lg-3">
                                    <label for="supplier-contact" class="visually-hidden">Contacto</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body">
                                            <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                                        </span>
                                        <input
                                            id="supplier-contact"
                                            name="contact"
                                            type="text"
                                            class="form-control @error('contact') is-invalid @enderror"
                                            placeholder="Contacto (opcional)…"
                                            wire:model.defer="contact"
                                            wire:keydown.enter.prevent="save"
                                            @if ($isEditingThisPage) wire:keydown.escape.prevent="cancelEdit" @endif
                                            autocomplete="off"
                                            maxlength="255"
                                            aria-label="Contacto del proveedor"
                                            aria-describedby="supplier-shortcuts"
                                        />
                                    </div>
                                    @error('contact')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-lg-5">
                                    <label for="supplier-notes" class="visually-hidden">Notas</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-body">
                                            <i class="bi bi-journal-text" aria-hidden="true"></i>
                                        </span>
                                        <input
                                            id="supplier-notes"
                                            name="notes"
                                            type="text"
                                            class="form-control @error('notes') is-invalid @enderror"
                                            placeholder="Notas (opcional)…"
                                            wire:model.defer="notes"
                                            wire:keydown.enter.prevent="save"
                                            @if ($isEditingThisPage) wire:keydown.escape.prevent="cancelEdit" @endif
                                            autocomplete="off"
                                            maxlength="1000"
                                            aria-label="Notas del proveedor"
                                            aria-describedby="supplier-shortcuts"
                                        />
                                    </div>
                                    @error('notes')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="catalogs-inline-editor__footer">
                                <div id="supplier-shortcuts" class="catalogs-inline-editor__keys">
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
                                        aria-label="{{ $isEditingThisPage ? 'Guardar cambios de proveedor' : 'Guardar nuevo proveedor' }}"
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
                                <th>Contacto</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $supplier)
                                @php($isRowEditing = $isEditingThisPage && $this->supplierId === $supplier->id)
                                <tr wire:key="supplier-row-{{ $supplier->id }}" @class(['catalogs-row-editing' => $isRowEditing])>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $supplier->name }}</div>
                                            <div class="small text-body-secondary">ID {{ $supplier->id }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ filled($supplier->contact) ? $supplier->contact : '-' }}
                                    </td>
                                    <td class="min-w-0">
                                        <div class="text-truncate" @if (filled($supplier->notes)) title="{{ $supplier->notes }}" @endif>
                                            {{ filled($supplier->notes) ? Str::limit($supplier->notes, 50) : '-' }}
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
                                                    wire:click="edit({{ $supplier->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="edit({{ $supplier->id }})"
                                                    aria-label="Editar proveedor {{ $supplier->name }}"
                                                >
                                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                    Editar
                                                </button>
                                            @endif
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $supplier->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar este proveedor?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete({{ $supplier->id }})"
                                                aria-label="Eliminar proveedor {{ $supplier->name }}"
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
                                                icon="bi-truck"
                                                title="No hay proveedores"
                                                description="Crea tu primer proveedor para usarlo en compras y contratos."
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
                    {{ $suppliers->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
