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
                    Activos asignados
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        0 elementos — Se habilita con Movimientos (Epic 5)
                    </p>
                </div>
            </div>

            {{-- Activos prestados --}}
            <div class="card mb-3">
                <div class="card-header">
                    Activos prestados
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        0 elementos — Se habilita con Movimientos (Epic 5)
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
