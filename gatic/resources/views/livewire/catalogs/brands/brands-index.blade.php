<div class="container position-relative">
    <x-ui.long-request target="save,delete" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    Marcas
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label for="brands-search" class="form-label">Buscar</label>
                            <input
                                id="brands-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="brand-name" class="form-label">
                                {{ $isEditing ? 'Editar marca' : 'Nueva marca' }}
                            </label>

                            <div class="input-group">
                                <input
                                    id="brand-name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Nombre"
                                    wire:model="name"
                                />

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
                                        Cancelar
                                    </button>
                                @endif
                            </div>

                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($brands as $brand)
                                    <tr>
                                        <td>{{ $brand->name }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="edit({{ $brand->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $brand->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar esta marca?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">No hay marcas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $brands->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
