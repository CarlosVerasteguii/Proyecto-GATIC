<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.index', $returnQuery) }}">
                    Volver
                </a>
                <div class="d-flex gap-2">
                    @if ($productIsSerialized)
                        <a
                            class="btn btn-sm btn-outline-secondary"
                            href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                        >
                            Activos
                        </a>
                    @else
                        @can('inventory.manage')
                            <a
                                class="btn btn-sm btn-primary"
                                href="{{ route('inventory.products.movements.quantity', ['product' => $product->id]) }}"
                            >
                                <i class="bi bi-arrow-left-right me-1"></i> Registrar movimiento
                            </a>
                        @endcan
                        @can('admin-only')
                            <a
                                class="btn btn-sm btn-warning"
                                href="{{ route('inventory.products.adjust', ['product' => $product->id] + $returnQuery) }}"
                            >
                                Ajustar inventario
                            </a>
                        @endcan
                    @endif
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total</div>
                            <div class="fs-4 fw-semibold">{{ $total }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-3">
                            <div class="text-muted small">Disponibles</div>
                            <div class="fs-4 fw-semibold">{{ $available }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-3">
                            <div class="text-muted small">No disponibles</div>
                            <div class="fs-4 fw-semibold">{{ $unavailable }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($productIsSerialized)
                <p class="text-muted small mb-3">
                    El <strong>Total</strong> excluye <strong>Retirado</strong> (baseline). El desglose lo muestra como informativo.
                </p>
            @endif

            <div class="card">
                <div class="card-header">
                    {{ $product?->name ?? 'Producto' }}
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Categoría</dt>
                        <dd class="col-sm-9">{{ $product?->category?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Marca</dt>
                        <dd class="col-sm-9">{{ $product?->brand?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($productIsSerialized)
                <div class="card mt-3">
                    <div class="card-header">Desglose por estado</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th class="text-end">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statusBreakdown as $row)
                                        <tr @class(['table-light' => $row['status'] === \App\Models\Asset::STATUS_RETIRED])>
                                            <td>{{ $row['status'] }}</td>
                                            <td class="text-end">{{ $row['count'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="text-muted small mb-0 mt-2">
                            <strong>Retirado</strong> se muestra como informativo y no cuenta en el inventario baseline por defecto.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
