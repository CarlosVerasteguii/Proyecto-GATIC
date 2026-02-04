<div class="container position-relative">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.detail-header :title="$contract->identifier" :subtitle="$contract->type_label">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Contratos', 'url' => auth()->user()?->can('inventory.manage') ? route('inventory.contracts.index') : null],
                        ['label' => $contract->identifier, 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <span class="badge bg-primary">{{ $contract->type_label }}</span>
                </x-slot:status>

                <x-slot:actions>
                    @can('inventory.manage')
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.contracts.edit', ['contract' => $contract->id]) }}">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar
                        </a>
                    @endcan
                </x-slot:actions>
            </x-ui.detail-header>

            <div class="card">
                <div class="card-header">
                    Informacion del contrato
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Identificador</dt>
                        <dd class="col-sm-9">{{ $contract->identifier }}</dd>

                        <dt class="col-sm-3">Tipo</dt>
                        <dd class="col-sm-9">{{ $contract->type_label }}</dd>

                        <dt class="col-sm-3">Proveedor</dt>
                        <dd class="col-sm-9">{{ $contract->supplier?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Vigencia</dt>
                        <dd class="col-sm-9">
                            @if ($contract->start_date || $contract->end_date)
                                <div class="d-flex align-items-center gap-2">
                                    @if ($contract->start_date)
                                        <span>
                                            <i class="bi bi-calendar-event me-1 text-muted"></i>
                                            {{ $contract->start_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sin fecha de inicio</span>
                                    @endif
                                    <span class="text-muted">al</span>
                                    @if ($contract->end_date)
                                        <span>
                                            <i class="bi bi-calendar-event me-1 text-muted"></i>
                                            {{ $contract->end_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sin fecha de fin</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Sin fechas definidas</span>
                            @endif
                        </dd>

                        @if ($contract->notes)
                            <dt class="col-sm-3">Notas</dt>
                            <dd class="col-sm-9">{{ $contract->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Activos vinculados</span>
                    <span class="badge bg-secondary">{{ $contract->assets->count() }}</span>
                </div>
                <div class="card-body">
                    @if ($contract->assets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial</th>
                                        <th>Producto</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contract->assets as $asset)
                                        <tr>
                                            <td>{{ $asset->serial }}</td>
                                            <td>{{ $asset->product?->name ?? '-' }}</td>
                                            <td>
                                                <x-ui.status-badge :status="$asset->status" />
                                            </td>
                                            <td class="text-end">
                                                <a
                                                    href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                >
                                                    Ver activo
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            No hay activos vinculados a este contrato.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
