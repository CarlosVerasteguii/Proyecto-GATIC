<div class="container position-relative">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Productos</span>
                    @can('inventory.manage')
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.create') }}">Nuevo producto</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label for="products-search" class="form-label">Buscar</label>
                            <input
                                id="products-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Stock total</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->category?->name ?? '-' }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td>{{ $product->category?->is_serialized ? 'Serializado' : 'Por cantidad' }}</td>
                                        <td class="text-end">
                                            @if ($product->category?->is_serialized)
                                                —
                                            @else
                                                {{ (string) $product->qty_total }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @can('inventory.manage')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.products.edit', ['product' => $product->id]) }}">
                                                    Editar
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">No hay productos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

