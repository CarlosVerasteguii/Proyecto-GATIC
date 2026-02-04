<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            {{-- Detail Header --}}
            <x-ui.detail-header :title="$product?->name ?? 'Producto'">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Productos', 'url' => route('inventory.products.index', $returnQuery)],
                        ['label' => $product?->name ?? 'Producto', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Total" :value="$total" />
                    <x-ui.detail-header-kpi label="Disponibles" :value="$available" variant="success" />
                    <x-ui.detail-header-kpi label="No disponibles" :value="$unavailable" variant="warning" />
                </x-slot:kpis>

                <x-slot:actions>
                    @if ($productIsSerialized)
                        <a
                            class="btn btn-sm btn-primary"
                            href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                        >
                            <i class="bi bi-hdd me-1" aria-hidden="true"></i>Ver activos
                        </a>
                    @else
                        <a
                            class="btn btn-sm btn-outline-info"
                            href="{{ route('inventory.products.kardex', ['product' => $product->id] + $returnQuery) }}"
                        >
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Ver kardex
                        </a>
                        @can('inventory.manage')
                            <a
                                class="btn btn-sm btn-primary"
                                href="{{ route('inventory.products.movements.quantity', ['product' => $product->id]) }}"
                            >
                                <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Registrar movimiento
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
                </x-slot:actions>
            </x-ui.detail-header>

            @if ($productIsSerialized)
                <p class="text-muted small mb-3">
                    El <strong>Total</strong> excluye <strong>Retirado</strong> (baseline). El desglose lo muestra como informativo.
                </p>
            @endif

            @php
                $isLowStock = ! $productIsSerialized
                    && $product?->low_stock_threshold !== null
                    && $product?->qty_total !== null
                    && $total <= $product->low_stock_threshold;
            @endphp

            @if ($isLowStock)
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                    <div>
                        <strong>Stock bajo:</strong> El stock actual ({{ $total }}) está en o por debajo del umbral configurado ({{ $product->low_stock_threshold }}).
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    Información del producto
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Categoría</dt>
                        <dd class="col-sm-9">{{ $product?->category?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Marca</dt>
                        <dd class="col-sm-9">{{ $product?->brand?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Proveedor</dt>
                        <dd class="col-sm-9">{{ $product?->supplier?->name ?? '-' }}</dd>

                        @if (! $productIsSerialized)
                            <dt class="col-sm-3">Umbral de stock bajo</dt>
                            <dd class="col-sm-9">
                                @if ($product?->low_stock_threshold !== null)
                                    {{ $product->low_stock_threshold }}
                                    @if ($isLowStock)
                                        <span class="badge text-bg-warning ms-2">Stock bajo</span>
                                    @endif
                                @else
                                    <span class="text-muted">No configurado</span>
                                @endif
                            </dd>
                        @endif
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
                                        <tr @class(['gatic-table-row-retired' => $row['status'] === \App\Models\Asset::STATUS_RETIRED])>
                                            <td><x-ui.status-badge :status="$row['status']" /></td>
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

            {{-- Notes panel --}}
            <livewire:ui.notes-panel
                :noteable-type="\App\Models\Product::class"
                :noteable-id="$product->id"
            />

            {{-- Attachments panel (Admin/Editor only) --}}
            @can('attachments.view')
                <livewire:ui.attachments-panel
                    :attachable-type="\App\Models\Product::class"
                    :attachable-id="$product->id"
                />
            @endcan
        </div>
    </div>
</div>
