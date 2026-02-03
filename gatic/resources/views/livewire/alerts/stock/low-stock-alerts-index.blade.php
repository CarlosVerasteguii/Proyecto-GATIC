<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Alertas', 'url' => null],
                        ]" />
                        <span class="fw-medium">Alertas de stock bajo</span>
                    </div>
                </div>

                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Productos por cantidad cuyo stock total está en o por debajo del umbral configurado.
                    </p>

                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th class="text-end">Stock actual</th>
                                    <th class="text-end">Umbral</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($alerts as $product)
                                    <tr>
                                        <td>
                                            <a
                                                href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                class="text-decoration-none"
                                            >
                                                {{ $product->name }}
                                            </a>
                                        </td>
                                        <td>{{ $product->category?->name ?? '—' }}</td>
                                        <td>{{ $product->brand?->name ?? '—' }}</td>
                                        <td class="text-end">
                                            <span class="fw-semibold text-warning">{{ $product->qty_total ?? 0 }}</span>
                                        </td>
                                        <td class="text-end">{{ $product->low_stock_threshold }}</td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Ver detalle
                                            </a>

                                            @can('inventory.manage')
                                                <a
                                                    href="{{ route('inventory.products.edit', ['product' => $product->id]) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    Editar
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-ui.empty-state
                                                icon="bi-check-circle"
                                                title="Sin alertas de stock bajo"
                                                description="No hay productos con stock por debajo de su umbral configurado."
                                                compact
                                            />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $alerts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
