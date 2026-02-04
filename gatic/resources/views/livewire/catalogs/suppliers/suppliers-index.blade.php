<div class="container position-relative">
    <x-ui.long-request target="save,delete" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    Proveedores
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-4">
                            <label for="suppliers-search" class="form-label">Buscar</label>
                            <input
                                id="suppliers-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>

                        <div class="col-12 col-md-8">
                            <label class="form-label">
                                {{ $isEditing ? 'Editar proveedor' : 'Nuevo proveedor' }}
                            </label>

                            <div class="row g-2">
                                <div class="col-12 col-sm-4">
                                    <input
                                        id="supplier-name"
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Nombre *"
                                        wire:model="name"
                                    />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-sm-3">
                                    <input
                                        id="supplier-contact"
                                        type="text"
                                        class="form-control @error('contact') is-invalid @enderror"
                                        placeholder="Contacto"
                                        wire:model="contact"
                                    />
                                    @error('contact')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-sm-3">
                                    <input
                                        id="supplier-notes"
                                        type="text"
                                        class="form-control @error('notes') is-invalid @enderror"
                                        placeholder="Notas"
                                        wire:model="notes"
                                    />
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-sm-2">
                                    <div class="btn-group w-100">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            wire:click="save"
                                            wire:loading.attr="disabled"
                                            wire:target="save"
                                        >
                                            Guardar
                                        </button>

                                        @if ($isEditing)
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                wire:click="cancelEdit"
                                                wire:loading.attr="disabled"
                                                wire:target="cancelEdit"
                                            >
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
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
                                    <tr>
                                        <td>{{ $supplier->name }}</td>
                                        <td>{{ $supplier->contact ?? '-' }}</td>
                                        <td>{{ filled($supplier->notes) ? Str::limit($supplier->notes, 50) : '-' }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="edit({{ $supplier->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $supplier->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar este proveedor?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No hay proveedores.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $suppliers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
