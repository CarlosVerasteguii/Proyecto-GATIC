<div class="container position-relative">
    <x-ui.long-request target="delete" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Categorías</span>
                    <a class="btn btn-sm btn-primary" href="{{ route('catalogs.categories.create') }}">Nueva categoría</a>
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label for="categories-search" class="form-label">Buscar</label>
                            <input
                                id="categories-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Serializado</th>
                                    <th>Requiere asset_tag</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr>
                                        <td>{{ $category->name }}</td>
                                        <td>{{ $category->is_serialized ? 'Sí' : 'No' }}</td>
                                        <td>{{ $category->requires_asset_tag ? 'Sí' : 'No' }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('catalogs.categories.edit', ['category' => $category->id]) }}">
                                                Editar
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $category->id }})"
                                                wire:confirm="Confirmas que deseas eliminar esta categoria?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No hay categorías.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
