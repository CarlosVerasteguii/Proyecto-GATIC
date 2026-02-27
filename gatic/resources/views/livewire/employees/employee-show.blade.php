<div class="container ops-page ops-employee-show-page">
    @php
        $assignedCount = $employee->assignedAssets->count();
        $loanedCount = $employee->loanedAssets->count();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.detail-header
                :title="$employee->rpe.' — '.$employee->name"
                subtitle="Empleado (RPE)"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Empleados', 'url' => route('employees.index')],
                        ['label' => $employee->rpe, 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Asignados" :value="$assignedCount" />
                    <x-ui.detail-header-kpi label="Prestados" :value="$loanedCount" />
                </x-slot:kpis>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Datos del empleado"
                subtitle="Información base"
                icon="person-badge"
                class="mb-3"
            >
                <dl class="row mb-0">
                    <div class="col-12 col-md-3">
                        <dt class="text-body-secondary small text-uppercase fw-semibold">RPE</dt>
                        <dd class="mb-2 fw-semibold">{{ $employee->rpe }}</dd>
                    </div>
                    <div class="col-12 col-md-3">
                        <dt class="text-body-secondary small text-uppercase fw-semibold">Nombre</dt>
                        <dd class="mb-2 fw-semibold">{{ $employee->name }}</dd>
                    </div>
                    <div class="col-12 col-md-3">
                        <dt class="text-body-secondary small text-uppercase fw-semibold">Departamento</dt>
                        <dd class="mb-2">{{ $employee->department ?? '—' }}</dd>
                    </div>
                    <div class="col-12 col-md-3">
                        <dt class="text-body-secondary small text-uppercase fw-semibold">Puesto</dt>
                        <dd class="mb-2">{{ $employee->job_title ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.section-card>

            <x-ui.section-card
                :title="'Activos asignados ('.$assignedCount.')'"
                :subtitle="'Total: '.number_format($assignedCount)"
                icon="hdd"
                class="mb-3"
            >
                @if ($employee->assignedAssets->isEmpty())
                    <x-ui.empty-state
                        icon="bi-hdd"
                        title="Sin activos asignados"
                        description="No hay activos asignados a este empleado"
                        compact
                    />
                @else
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-striped align-middle mb-0 table-gatic-head ops-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Serial</th>
                                    <th>Asset tag</th>
                                    <th class="text-end" style="width: 1%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employee->assignedAssets as $asset)
                                    <tr>
                                        <td class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $asset->product?->name ?? '-' }}</div>
                                            <div class="text-body-secondary small">ID {{ $asset->id }}</div>
                                        </td>
                                        <td>{{ $asset->serial }}</td>
                                        <td>{{ $asset->asset_tag ?? '-' }}</td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                class="btn btn-outline-secondary ops-action-btn"
                                                aria-label="Ver activo {{ $asset->serial }}"
                                                title="Ver"
                                            >
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.section-card>

            <x-ui.section-card
                :title="'Activos prestados ('.$loanedCount.')'"
                :subtitle="'Total: '.number_format($loanedCount)"
                icon="box-arrow-up-right"
                class="mb-3"
            >
                @if ($employee->loanedAssets->isEmpty())
                    <x-ui.empty-state
                        icon="bi-box-arrow-up-right"
                        title="Sin activos prestados"
                        description="No hay activos prestados a este empleado"
                        compact
                    />
                @else
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-striped align-middle mb-0 table-gatic-head ops-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Serial</th>
                                    <th>Asset tag</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end" style="width: 1%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employee->loanedAssets as $asset)
                                    <tr>
                                        <td class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $asset->product?->name ?? '-' }}</div>
                                            <div class="text-body-secondary small">ID {{ $asset->id }}</div>
                                        </td>
                                        <td>{{ $asset->serial }}</td>
                                        <td>{{ $asset->asset_tag ?? '-' }}</td>
                                        <td>
                                            @if ($asset->loan_due_date)
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                                    {{ $asset->loan_due_date->format('d/m/Y') }}
                                                </small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                class="btn btn-outline-secondary ops-action-btn"
                                                aria-label="Ver activo {{ $asset->serial }}"
                                                title="Ver"
                                            >
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.section-card>

            <livewire:ui.timeline-panel
                :entity-type="\App\Models\Employee::class"
                :entity-id="$employee->id"
            />

            <livewire:ui.notes-panel
                :noteable-type="\App\Models\Employee::class"
                :noteable-id="$employee->id"
            />

            @can('attachments.view')
                <livewire:ui.attachments-panel
                    :attachable-type="\App\Models\Employee::class"
                    :attachable-id="$employee->id"
                />
            @endcan
        </div>
    </div>
</div>
