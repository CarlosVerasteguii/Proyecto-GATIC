<div class="container position-relative">
    <x-ui.long-request target="restore, purge, emptyTrash" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Papelera de catálogos</span>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        wire:click="emptyTrash"
                        wire:confirm="¿Estás seguro de vaciar toda la papelera de esta pestaña? Esta acción es IRREVERSIBLE."
                        wire:loading.attr="disabled"
                        wire:target="emptyTrash"
                    >
                        <span wire:loading.remove wire:target="emptyTrash">Vaciar papelera</span>
                        <span wire:loading wire:target="emptyTrash">Procesando...</span>
                    </button>
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link @if ($tab === 'categories') active @endif"
                                wire:click="setTab('categories')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Categorias
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link @if ($tab === 'brands') active @endif"
                                wire:click="setTab('brands')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Marcas
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link @if ($tab === 'locations') active @endif"
                                wire:click="setTab('locations')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Ubicaciones
                            </button>
                        </li>
                    </ul>

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label for="catalogs-trash-search" class="form-label">Buscar</label>
                            <input
                                id="catalogs-trash-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>
                    </div>

                    @if ($tab === 'categories' && $categories)
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
                                            <td>{{ $category->is_serialized ? 'Si' : 'No' }}</td>
                                            <td>{{ $category->requires_asset_tag ? 'Si' : 'No' }}</td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('categories', {{ $category->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar esta categoría?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('categories', {{ $category->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE esta categoría? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted">No hay categorias eliminadas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $categories->links() }}
                        </div>
                    @endif

                    @if ($tab === 'brands' && $brands)
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
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('brands', {{ $brand->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar esta marca?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('brands', {{ $brand->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE esta marca? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">No hay marcas eliminadas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $brands->links() }}
                        </div>
                    @endif

                    @if ($tab === 'locations' && $locations)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($locations as $location)
                                        <tr>
                                            <td>{{ $location->name }}</td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('locations', {{ $location->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar esta ubicación?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('locations', {{ $location->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE esta ubicación? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">No hay ubicaciones eliminadas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $locations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
