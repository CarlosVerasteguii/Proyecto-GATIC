<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    Empleado: {{ $employee->rpe }} — {{ $employee->name }}
                </h4>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    Volver
                </a>
            </div>

            {{-- Datos del empleado --}}
            <div class="card mb-3">
                <div class="card-header">
                    Datos del empleado
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>RPE:</strong>
                            <p class="mb-2">{{ $employee->rpe }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Nombre:</strong>
                            <p class="mb-2">{{ $employee->name }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Departamento:</strong>
                            <p class="mb-2">{{ $employee->department ?? '—' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Puesto:</strong>
                            <p class="mb-2">{{ $employee->job_title ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activos asignados --}}
            <div class="card mb-3">
                <div class="card-header">
                    Activos asignados ({{ $employee->assignedAssets->count() }})
                </div>
                <div class="card-body">
                    @if ($employee->assignedAssets->isEmpty())
                        <p class="text-muted mb-0">No hay activos asignados a este empleado</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Serial</th>
                                        <th>Asset tag</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->assignedAssets as $asset)
                                        <tr>
                                            <td>{{ $asset->product?->name ?? '-' }}</td>
                                            <td>{{ $asset->serial }}</td>
                                            <td>{{ $asset->asset_tag ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}" class="btn btn-sm btn-outline-secondary">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Activos prestados --}}
            <div class="card mb-3">
                <div class="card-header">
                    Activos prestados ({{ $employee->loanedAssets->count() }})
                </div>
                <div class="card-body">
                    @if ($employee->loanedAssets->isEmpty())
                        <p class="text-muted mb-0">No hay activos prestados a este empleado</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Serial</th>
                                        <th>Asset tag</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->loanedAssets as $asset)
                                        <tr>
                                            <td>{{ $asset->product?->name ?? '-' }}</td>
                                            <td>{{ $asset->serial }}</td>
                                            <td>{{ $asset->asset_tag ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}" class="btn btn-sm btn-outline-secondary">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes panel --}}
            <livewire:ui.notes-panel
                :noteable-type="\App\Models\Employee::class"
                :noteable-id="$employee->id"
            />

            {{-- Attachments panel (Admin/Editor only) --}}
            @can('attachments.view')
                <livewire:ui.attachments-panel
                    :attachable-type="\App\Models\Employee::class"
                    :attachable-id="$employee->id"
                />
            @endcan
        </div>
    </div>
</div>
