<div class="container position-relative ops-page ops-employees-page">
    <x-ui.long-request target="save,delete" />

    @php
        $hasSearch = trim($this->search) !== '';
        $resultsCount = $employees->total();
        $clearHidden = ! $hasSearch;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Empleados"
                subtitle="Directorio RPE para préstamos y asignaciones (no son usuarios del sistema)."
                filterId="employees-filters"
                :filtersCollapsible="false"
                class="ops-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Empleados', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <span class="dash-chip">
                        Total <strong>{{ number_format($this->totalEmployees) }}</strong>
                    </span>
                    @if ($hasSearch)
                        <span class="dash-chip">
                            Resultados <strong>{{ number_format($resultsCount) }}</strong>
                        </span>
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
                            name="search"
                            type="search"
                            class="form-control"
                            placeholder="Buscar por RPE o nombre…"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Buscar empleado por RPE o nombre"
                            autocomplete="off"
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary{{ $clearHidden ? ' invisible' : '' }}"
                            wire:click="clearSearch"
                            wire:loading.attr="disabled"
                            wire:target="clearSearch"
                            aria-label="Limpiar búsqueda"
                            title="Limpiar búsqueda"
                            @if ($clearHidden) disabled aria-hidden="true" tabindex="-1" @endif
                        >
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </x-slot:search>

                <div class="small text-body-secondary mb-2" aria-live="polite">
                    <span>Mostrando {{ number_format($resultsCount) }} resultado{{ $resultsCount === 1 ? '' : 's' }}.</span>
                    <span
                        class="ms-2 align-items-center gap-2"
                        wire:loading.inline-flex
                        wire:target="search,clearSearch"
                    >
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        Buscando…
                    </span>
                </div>

                <div class="card ops-inline-editor mb-3 {{ $isEditing ? 'ops-inline-editor--editing' : '' }}">
                    <div class="card-header d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div class="min-w-0">
                            <p class="ops-inline-editor__heading">
                                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                                <span>{{ $isEditing ? 'Editar empleado' : 'Nuevo empleado' }}</span>
                            </p>
                            <p class="ops-inline-editor__subtext">
                                {{ $isEditing ? 'Actualiza la información para mantener trazabilidad correcta en movimientos.' : 'Crea el empleado para poder asignar o prestar activos.' }}
                            </p>
                        </div>

                        @if ($isEditing)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="cancelEdit"
                                wire:loading.attr="disabled"
                                wire:target="cancelEdit"
                            >
                                Cancelar edición
                            </button>
                        @endif
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="employee-rpe" class="form-label">RPE</label>
                                <input
                                    id="employee-rpe"
                                    type="text"
                                    class="form-control @error('rpe') is-invalid @enderror"
                                    placeholder="Ej: ABC123"
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
                                    placeholder="Opcional"
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
                                    placeholder="Opcional"
                                    wire:model="jobTitle"
                                    autocomplete="off"
                                />
                                @error('jobTitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="ops-inline-editor__footer">
                            <div class="ops-inline-editor__hint">
                                Campos obligatorios: <strong>RPE</strong> y <strong>Nombre</strong>.
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    wire:target="save"
                                >
                                    <span wire:loading.remove wire:target="save">Guardar</span>
                                    <span wire:loading.inline wire:target="save">
                                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                        Guardando…
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="table-responsive border rounded-3"
                    wire:loading.class="opacity-50 pe-none"
                    wire:target="search,clearSearch,edit,delete,save,cancelEdit"
                >
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head ops-table" data-column-table="employees">
                        <thead>
                            <tr>
                                <th data-column-key="rpe" data-column-required="true">RPE</th>
                                <th data-column-key="name">Nombre</th>
                                <th data-column-key="department">Departamento</th>
                                <th data-column-key="job_title">Puesto</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end" style="width: 1%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr wire:key="employee-row-{{ $employee->id }}">
                                    <td class="fw-semibold">{{ $employee->rpe }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->department ?? '—' }}</td>
                                    <td>{{ $employee->job_title ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('employees.show', $employee->id) }}"
                                                class="btn btn-outline-secondary ops-action-btn"
                                                aria-label="Ver ficha del empleado {{ $employee->rpe }}"
                                                title="Ver ficha"
                                            >
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary ops-action-btn"
                                                wire:click="edit({{ $employee->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                                aria-label="Editar empleado {{ $employee->rpe }}"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger ops-action-btn"
                                                wire:click="delete({{ $employee->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar este empleado?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                                aria-label="Eliminar empleado {{ $employee->rpe }}"
                                                title="Eliminar"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
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
                                                description="Crea tu primer empleado para poder asignar o prestar activos."
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
