<div class="container position-relative">
    <x-ui.long-request target="save,delete" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    Empleados
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="employees-search" class="form-label">Buscar</label>
                            <input
                                id="employees-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por RPE o nombre."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">{{ $isEditing ? 'Editar empleado' : 'Nuevo empleado' }}</h6>

                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label for="employee-rpe" class="form-label">RPE</label>
                                    <input
                                        id="employee-rpe"
                                        type="text"
                                        class="form-control @error('rpe') is-invalid @enderror"
                                        placeholder="RPE"
                                        wire:model="rpe"
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
                                    />
                                    @error('jobTitle')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    wire:target="save"
                                >
                                    Guardar
                                </button>

                                @if ($isEditing)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        wire:click="cancelEdit"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelEdit"
                                    >
                                        Cancelar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>RPE</th>
                                    <th>Nombre</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->rpe }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td>{{ $employee->department ?? '—' }}</td>
                                        <td>{{ $employee->job_title ?? '—' }}</td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('employees.show', $employee->id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Ver ficha
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="edit({{ $employee->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="edit"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $employee->id }})"
                                                wire:confirm="¿Confirmas que deseas eliminar este empleado?"
                                                wire:loading.attr="disabled"
                                                wire:target="delete"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No hay empleados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
