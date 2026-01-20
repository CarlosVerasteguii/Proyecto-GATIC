<div class="container position-relative">
    <x-ui.long-request />
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Tareas Pendientes</span>
                    <a class="btn btn-sm btn-primary" href="{{ route('pending-tasks.create') }}">Nueva tarea</a>
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-3">
                            <label for="filter-status" class="form-label">Estado</label>
                            <select
                                id="filter-status"
                                class="form-select"
                                wire:model.live="statusFilter"
                                aria-label="Filtrar por estado"
                            >
                                <option value="">Todos</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filter-type" class="form-label">Tipo de operación</label>
                            <select
                                id="filter-type"
                                class="form-select"
                                wire:model.live="typeFilter"
                                aria-label="Filtrar por tipo"
                            >
                                <option value="">Todos</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            @if ($this->hasActiveFilters())
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary w-100"
                                    wire:click="clearFilters"
                                    aria-label="Limpiar todos los filtros"
                                >
                                    Limpiar
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Renglones</th>
                                    <th>Creador</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tasks as $task)
                                    <tr>
                                        <td>{{ $task->id }}</td>
                                        <td>{{ $task->type->label() }}</td>
                                        <td>
                                            <span class="badge {{ $task->status->badgeClass() }}">
                                                {{ $task->status->label() }}
                                            </span>
                                        </td>
                                        <td>{{ $task->lines_count }}</td>
                                        <td>{{ $task->creator->name ?? '-' }}</td>
                                        <td>{{ $task->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <a
                                                class="btn btn-sm btn-outline-secondary"
                                                href="{{ route('pending-tasks.show', $task->id) }}"
                                            >
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No hay tareas pendientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $tasks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
