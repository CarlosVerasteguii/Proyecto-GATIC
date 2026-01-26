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
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}">
                        Volver
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id]) }}">
                        Producto
                    </a>
                </div>
                <div class="d-flex gap-2">
                    @can('inventory.manage')
                        @if (\App\Support\Assets\AssetStatusTransitions::canAssign($asset->status))
                            <a class="btn btn-sm btn-success" href="{{ route('inventory.products.assets.assign', ['product' => $product->id, 'asset' => $asset->id]) }}">
                                <i class="bi bi-person-check me-1"></i> Asignar
                            </a>
                        @endif
                        @if (\App\Support\Assets\AssetStatusTransitions::canLoan($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.loan', ['product' => $product->id, 'asset' => $asset->id]) }}">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Prestar
                            </a>
                        @endif
                        @if (\App\Support\Assets\AssetStatusTransitions::canReturn($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.return', ['product' => $product->id, 'asset' => $asset->id]) }}">
                                <i class="bi bi-arrow-return-left me-1"></i> Devolver
                            </a>
                        @endif
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.assets.edit', ['product' => $product->id, 'asset' => $asset->id]) }}">
                            Editar
                        </a>
                    @endcan
                    @can('admin-only')
                        <a class="btn btn-sm btn-warning" href="{{ route('inventory.products.assets.adjust', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                            Ajustar
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Detalle del Activo
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Producto</dt>
                        <dd class="col-sm-9">{{ $product->name }}</dd>

                        <dt class="col-sm-3">Serial</dt>
                        <dd class="col-sm-9">{{ $asset->serial }}</dd>

                        <dt class="col-sm-3">Asset tag</dt>
                        <dd class="col-sm-9">{{ $asset->asset_tag ?? '-' }}</dd>

                        <dt class="col-sm-3">Estado</dt>
                        <dd class="col-sm-9">
                            <x-ui.status-badge :status="$asset->status" />
                        </dd>

                        <dt class="col-sm-3">Ubicación</dt>
                        <dd class="col-sm-9">{{ $asset->location?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    Tenencia actual
                </div>
                <div class="card-body">
                    @php
                        $hasHolder = in_array($asset->status, [\App\Models\Asset::STATUS_ASSIGNED, \App\Models\Asset::STATUS_LOANED], true);
                    @endphp

                    @if (! $hasHolder)
                        <p class="mb-0 text-muted">N/A — El activo está disponible</p>
                    @elseif ($asset->currentEmployee)
                        <div class="d-flex align-items-center gap-2">
                            @if ($asset->status === \App\Models\Asset::STATUS_ASSIGNED)
                                <span class="badge bg-warning text-dark">Asignado</span>
                            @else
                                <span class="badge bg-info text-dark">Prestado</span>
                            @endif
                            <a href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}" class="text-decoration-none">
                                <strong>{{ $asset->currentEmployee->rpe }}</strong> — {{ $asset->currentEmployee->name }}
                            </a>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Sin tenencia registrada (estado legacy o ajuste manual)</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes panel --}}
            <livewire:ui.notes-panel
                :noteable-type="\App\Models\Asset::class"
                :noteable-id="$asset->id"
            />

            {{-- Attachments panel (Admin/Editor only) --}}
            @can('attachments.view')
                <livewire:ui.attachments-panel
                    :attachable-type="\App\Models\Asset::class"
                    :attachable-id="$asset->id"
                />
            @endcan
        </div>
    </div>
</div>
