<div class="container position-relative">
    <x-ui.long-request target="restore, purge, emptyTrash" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Papelera</span>
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
                                class="nav-link @if ($tab === 'products') active @endif"
                                wire:click="setTab('products')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Productos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link @if ($tab === 'assets') active @endif"
                                wire:click="setTab('assets')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Activos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link @if ($tab === 'employees') active @endif"
                                wire:click="setTab('employees')"
                                wire:loading.attr="disabled"
                                wire:target="setTab"
                            >
                                Empleados
                            </button>
                        </li>
                    </ul>

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label for="trash-search" class="form-label">Buscar</label>
                            <input
                                id="trash-search"
                                type="text"
                                class="form-control"
                                placeholder="@if ($tab === 'products') Buscar por nombre @elseif ($tab === 'assets') Buscar por serial o asset_tag @else Buscar por RPE o nombre @endif"
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>
                    </div>

                    {{-- Products Tab --}}
                    @if ($tab === 'products' && $products)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Marca</th>
                                        <th>Eliminado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($products as $product)
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->category?->name ?? '—' }}</td>
                                            <td>{{ $product->brand?->name ?? '—' }}</td>
                                            <td>
                                                <small class="text-muted">{{ $product->deleted_at?->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('products', {{ $product->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar este producto?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('products', {{ $product->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE este producto? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted">No hay productos eliminados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $products->links() }}
                        </div>
                    @endif

                    {{-- Assets Tab --}}
                    @if ($tab === 'assets' && $assets)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial</th>
                                        <th>Asset Tag</th>
                                        <th>Producto</th>
                                        <th>Estado</th>
                                        <th>Eliminado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assets as $asset)
                                        <tr>
                                            <td>{{ $asset->serial }}</td>
                                            <td>{{ $asset->asset_tag ?? '—' }}</td>
                                            <td>{{ $asset->product?->name ?? '—' }}</td>
                                            <td>{{ $asset->status }}</td>
                                            <td>
                                                <small class="text-muted">{{ $asset->deleted_at?->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('assets', {{ $asset->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar este activo?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('assets', {{ $asset->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE este activo? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-muted">No hay activos eliminados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $assets->links() }}
                        </div>
                    @endif

                    {{-- Employees Tab --}}
                    @if ($tab === 'employees' && $employees)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>RPE</th>
                                        <th>Nombre</th>
                                        <th>Departamento</th>
                                        <th>Eliminado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employees as $employee)
                                        <tr>
                                            <td>{{ $employee->rpe }}</td>
                                            <td>{{ $employee->name }}</td>
                                            <td>{{ $employee->department ?? '—' }}</td>
                                            <td>
                                                <small class="text-muted">{{ $employee->deleted_at?->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    wire:click="restore('employees', {{ $employee->id }})"
                                                    wire:confirm="¿Confirmas que deseas restaurar este empleado?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restore"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="purge('employees', {{ $employee->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar PERMANENTEMENTE este empleado? Esta acción es IRREVERSIBLE."
                                                    wire:loading.attr="disabled"
                                                    wire:target="purge"
                                                >
                                                    Purgar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted">No hay empleados eliminados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $employees->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
