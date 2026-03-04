<div class="container position-relative operations-page operations-employees-page">
    <x-ui.long-request target="save,delete,edit,cancelEdit" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $employees->total();
        $isEditingThisPage = (bool) $isEditing;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Empleados"
                subtitle="Gestiona empleados para asignaciones, préstamos y trazabilidad."
                filterId="employees-filters"
                :filtersCollapsible="false"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Operaciones', 'url' => route('employees.index')],
                        ['label' => 'Empleados', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Total <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>
                    @if ($hasSearch)
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Resultados <strong>{{ number_format($resultsCount) }}</strong>
                        </x-ui.badge>
                    @endif
                    @if ($isEditingThisPage)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false" icon="bi-pencil-square">Editando</x-ui.badge>
                    @endif

                    <x-ui.column-manager table="employees" />
                </x-slot:actions>

                <x-slot:search>
                    <label for="employees-search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="employees-search"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por RPE o nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar empleado por RPE o nombre"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-9">
                        <div class="card bg-body-tertiary">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <div class="fw-semibold">
                                            <i class="bi bi-person-badge me-1" aria-hidden="true"></i>
                                            {{ $isEditingThisPage ? 'Editar empleado' : 'Nuevo empleado' }}
                                        </div>
                                        <div class="small text-body-secondary">
                                            @if ($isEditingThisPage)
                                                Actualiza la información del empleado seleccionado.
                                            @else
                                                Agrega empleados para usarlos en asignaciones y préstamos.
                                            @endif
                                        </div>
                                    </div>

                                    @if ($isEditingThisPage)
                                        <span class="small text-body-secondary">ID {{ $this->employeeId }}</span>
                                    @endif
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-12 col-md-3">
                                        <label for="employee-rpe" class="form-label">RPE</label>
                                        <input
                                            id="employee-rpe"
                                            type="text"
                                            class="form-control @error('rpe') is-invalid @enderror"
                                            placeholder="RPE"
                                            wire:model="rpe"
                                            autocomplete="off"
                                        />
                                        @error('rpe')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label for="employee-name" class="form-label">Nombre</label>
                                        <input
                                            id="employee-name"
                                            type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="Nombre completo"
                                            wire:model="name"
                                            autocomplete="off"
                                        />
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label for="employee-department" class="form-label">Departamento</label>
                                        <input
                                            id="employee-department"
                                            type="text"
                                            class="form-control @error('department') is-invalid @enderror"
                                            placeholder="Departamento (opcional)"
                                            wire:model="department"
                                            autocomplete="off"
                                        />
                                        @error('department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label for="employee-job-title" class="form-label">Puesto</label>
                                        <input
                                            id="employee-job-title"
                                            type="text"
                                            class="form-control @error('jobTitle') is-invalid @enderror"
                                            placeholder="Puesto (opcional)"
                                            wire:model="jobTitle"
                                            autocomplete="off"
                                        />
                                        @error('jobTitle')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        wire:click="save"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                        aria-label="{{ $isEditingThisPage ? 'Guardar cambios de empleado' : 'Guardar nuevo empleado' }}"
                                    >
                                        <span wire:loading.remove wire:target="save">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                            {{ $isEditingThisPage ? 'Guardar cambios' : 'Guardar' }}
                                        </span>
                                        <span wire:loading.inline wire:target="save">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                Guardando…
                                            </span>
                                        </span>
                                    </button>

                                    @if ($isEditingThisPage)
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            wire:click="cancelEdit"
                                            wire:loading.attr="disabled"
                                            wire:target="cancelEdit"
                                        >
                                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasSearch)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="$set('search', '')"
                            aria-label="Limpiar búsqueda"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} resultado{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head" data-column-table="employees">
                        <thead>
                            <tr>
                                <th data-column-key="rpe" data-column-required="true">RPE</th>
                                <th data-column-key="name">Nombre</th>
                                <th data-column-key="department">Departamento</th>
                                <th data-column-key="job_title">Puesto</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr wire:key="employee-row-{{ $employee->id }}">
                                    <td class="fw-semibold">{{ $employee->rpe }}</td>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $employee->name }}</div>
                                            <div class="small text-body-secondary">ID {{ $employee->id }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $employee->department ?? '—' }}</td>
                                    <td>{{ $employee->job_title ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('employees.show', $employee->id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                aria-label="Ver ficha de {{ $employee->rpe }}"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver ficha
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="edit({{ $employee->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                                aria-label="Editar empleado {{ $employee->rpe }}"
                                            >
                                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $employee->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar este empleado?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                                aria-label="Eliminar empleado {{ $employee->rpe }}"
                                            >
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        @if ($hasSearch)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-person-badge"
                                                title="No hay empleados"
                                                description="Crea tu primer empleado para comenzar a registrar asignaciones y préstamos."
                                                compact
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $employees->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
