<div class="container position-relative operations-page operations-employees-page">
    @php
        $assignedAssets = $employee->assignedAssets;
        $loanedAssets = $employee->loanedAssets;
        $assignedAssetsCount = $assignedAssets->count();
        $loanedAssetsCount = $loanedAssets->count();
        $totalAssetsCount = $assignedAssetsCount + $loanedAssetsCount;

        $department = is_string($employee->department) && trim($employee->department) !== ''
            ? $employee->department
            : null;
        $jobTitle = is_string($employee->job_title) && trim($employee->job_title) !== ''
            ? $employee->job_title
            : null;

        $isProfileIncomplete = $department === null || $jobTitle === null;
        $profileTone = $isProfileIncomplete ? 'warning' : 'success';
        $profileLabel = $isProfileIncomplete ? 'Ficha incompleta' : 'Ficha completa';

        $headerSubtitle = collect([
            $department,
            $jobTitle,
        ])->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->implode(' · ');

        if ($headerSubtitle === '') {
            $headerSubtitle = 'Sin departamento ni puesto capturados.';
        }

        $today = \Illuminate\Support\Carbon::today();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header :title="$employee->name" :subtitle="$headerSubtitle">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Operaciones', 'url' => null],
                        ['label' => 'Empleados', 'url' => route('employees.index')],
                        ['label' => $employee->rpe, 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        RPE {{ $employee->rpe }}
                    </x-ui.badge>
                    <x-ui.badge :tone="$profileTone" variant="compact" :with-rail="false">
                        {{ $profileLabel }}
                    </x-ui.badge>
                </x-slot:status>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Total Activos" :value="$totalAssetsCount" />
                    <x-ui.detail-header-kpi label="Asignados" :value="$assignedAssetsCount" variant="info" />
                    <x-ui.detail-header-kpi label="Prestados" :value="$loanedAssetsCount" variant="warning" />
                </x-slot:kpis>

                <x-slot:actions>
                    <a
                        href="{{ route('employees.index') }}"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver a empleados
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Resumen del empleado"
                subtitle="Datos operativos para asignaciones, préstamos y trazabilidad."
                icon="bi-person-vcard"
                class="mb-4"
            >
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">RPE</div>
                            <div class="fw-semibold mt-2">{{ $employee->rpe }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Departamento</div>
                            <div class="fw-semibold mt-2">{{ $department ?? 'Sin departamento capturado' }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Puesto</div>
                            <div class="fw-semibold mt-2">{{ $jobTitle ?? 'Sin puesto capturado' }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Estado de la ficha</div>
                            <div class="mt-2">
                                <x-ui.badge :tone="$profileTone" variant="compact" :with-rail="false">
                                    {{ $profileLabel }}
                                </x-ui.badge>
                            </div>
                            <div class="small text-body-secondary mt-2">
                                @if ($isProfileIncomplete)
                                    Completa el departamento y el puesto para mejorar la trazabilidad.
                                @else
                                    La información básica del empleado está lista para operar.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            <div class="mb-3">
                <h2 class="h5 mb-1">Activos actuales</h2>
                <p class="small text-body-secondary mb-0">
                    Consulta el inventario actualmente resguardado o prestado a este empleado.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <x-ui.section-card
                        title="Activos asignados"
                        subtitle="Resguardo permanente del empleado."
                        icon="bi-person-check"
                        bodyClass="p-0"
                        class="h-100"
                    >
                        <x-slot:actions>
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                {{ number_format($assignedAssetsCount) }}
                            </x-ui.badge>
                        </x-slot:actions>

                        @if ($assignedAssetsCount === 0)
                            <div class="p-4">
                                <x-ui.empty-state
                                    icon="bi-person-check"
                                    title="Sin activos asignados"
                                    description="Este empleado no tiene activos en resguardo permanente."
                                    compact
                                />
                            </div>
                        @else
                            <div class="table-responsive-xl">
                                <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                    <thead>
                                        <tr>
                                            <th scope="col">Activo</th>
                                            <th scope="col">Identificadores</th>
                                            <th scope="col">Estado</th>
                                            <th scope="col" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assignedAssets as $asset)
                                            <tr wire:key="employee-assigned-asset-{{ $asset->id }}">
                                                <td class="min-w-0">
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-truncate">
                                                            {{ $asset->product?->name ?? 'Producto no disponible' }}
                                                        </div>
                                                        <div class="small text-body-secondary">Activo ID {{ $asset->id }}</div>
                                                    </div>
                                                </td>
                                                <td class="text-nowrap">
                                                    <div class="fw-semibold">{{ $asset->serial }}</div>
                                                    <div class="small text-body-secondary">
                                                        Asset tag: {{ $asset->asset_tag ?? '—' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <x-ui.badge tone="info" variant="compact" :with-rail="false">
                                                        Asignado
                                                    </x-ui.badge>
                                                    <div class="small text-body-secondary mt-2">
                                                        Activo en resguardo operativo.
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <a
                                                        href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        aria-label="Ver activo {{ $asset->serial }}"
                                                    >
                                                        <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                        Ver Activo
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.section-card>
                </div>

                <div class="col-12 col-xl-6">
                    <x-ui.section-card
                        title="Activos prestados"
                        subtitle="Préstamos vigentes para este empleado."
                        icon="bi-box-arrow-up-right"
                        bodyClass="p-0"
                        class="h-100"
                    >
                        <x-slot:actions>
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                {{ number_format($loanedAssetsCount) }}
                            </x-ui.badge>
                        </x-slot:actions>

                        @if ($loanedAssetsCount === 0)
                            <div class="p-4">
                                <x-ui.empty-state
                                    icon="bi-box-arrow-up-right"
                                    title="Sin activos prestados"
                                    description="Este empleado no tiene préstamos activos en este momento."
                                    compact
                                />
                            </div>
                        @else
                            <div class="table-responsive-xl">
                                <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                    <thead>
                                        <tr>
                                            <th scope="col">Activo</th>
                                            <th scope="col">Identificadores</th>
                                            <th scope="col">Vencimiento</th>
                                            <th scope="col" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($loanedAssets as $asset)
                                            @php
                                                $loanDueDate = $asset->loan_due_date;
                                                $loanDueTone = 'success';
                                                $loanDueLabel = 'En tiempo';

                                                if ($loanDueDate && $loanDueDate->lt($today)) {
                                                    $loanDueTone = 'danger';
                                                    $loanDueLabel = 'Vencido';
                                                } elseif ($loanDueDate && $loanDueDate->lte($today->copy()->addDays(7))) {
                                                    $loanDueTone = 'warning';
                                                    $loanDueLabel = 'Por vencer';
                                                }
                                            @endphp
                                            <tr wire:key="employee-loaned-asset-{{ $asset->id }}">
                                                <td class="min-w-0">
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-truncate">
                                                            {{ $asset->product?->name ?? 'Producto no disponible' }}
                                                        </div>
                                                        <div class="small text-body-secondary">Activo ID {{ $asset->id }}</div>
                                                    </div>
                                                </td>
                                                <td class="text-nowrap">
                                                    <div class="fw-semibold">{{ $asset->serial }}</div>
                                                    <div class="small text-body-secondary">
                                                        Asset tag: {{ $asset->asset_tag ?? '—' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($loanDueDate)
                                                        <div class="small text-body-secondary">
                                                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                                            Vence {{ $loanDueDate->format('d/m/Y') }}
                                                        </div>
                                                        <div class="mt-2">
                                                            <x-ui.badge :tone="$loanDueTone" variant="compact" :with-rail="false">
                                                                {{ $loanDueLabel }}
                                                            </x-ui.badge>
                                                        </div>
                                                    @else
                                                        <span class="small text-body-secondary">Sin vencimiento registrado.</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a
                                                        href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        aria-label="Ver activo {{ $asset->serial }}"
                                                    >
                                                        <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                        Ver Activo
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.section-card>
                </div>
            </div>

            <div class="mt-4 mb-3">
                <h2 class="h5 mb-1">Trazabilidad</h2>
                <p class="small text-body-secondary mb-0">
                    Revisa la actividad histórica, las notas operativas y los adjuntos asociados al empleado.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <livewire:ui.timeline-panel
                        :entity-type="\App\Models\Employee::class"
                        :entity-id="$employee->id"
                    />
                </div>

                <div class="col-12 col-xl-5">
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
    </div>
</div>
