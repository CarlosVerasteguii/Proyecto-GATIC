<div class="container position-relative operations-page operations-pending-tasks-page">
    <x-ui.long-request />

    @php
        $resultsCount = $tasks->total();
        $hasFilters = $this->hasActiveFilters();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Tareas pendientes"
                subtitle="Procesa movimientos en lote con locks para evitar duplicados."
                filterId="pending-tasks-filters"
                :filtersCollapsible="false"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Operaciones', 'url' => route('pending-tasks.index')],
                        ['label' => 'Tareas pendientes', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Resultados <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>

                    <x-ui.column-manager table="pending-tasks" />
                    <livewire:pending-tasks.quick-stock-in :key="'quick-stock-in-index'" />
                    <livewire:pending-tasks.quick-retirement :key="'quick-retirement-index'" />
                    <livewire:pending-tasks.pending-task-opener :key="'pending-task-opener-index'" />

                    <a class="btn btn-sm btn-primary" href="{{ route('pending-tasks.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                        Nueva tarea
                    </a>
                </x-slot:actions>

                <x-slot:filters>
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
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar filtros"
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
                    <table
                        class="table table-sm table-striped align-middle mb-0 table-gatic-head"
                        data-column-table="pending-tasks"
                    >
                        <thead>
                            <tr>
                                <th data-column-key="id" data-column-required="true">ID</th>
                                <th data-column-key="type">Tipo</th>
                                <th data-column-key="status">Estado</th>
                                <th data-column-key="lines">Renglones</th>
                                <th data-column-key="creator">Creador</th>
                                <th data-column-key="created_at">Fecha</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                @php
                                    $isQuickCapture = $task->isQuickCaptureTask();
                                    $quickKind = is_array($task->payload ?? null) ? ($task->payload['kind'] ?? null) : null;
                                    $quickLabel = $quickKind === 'quick_stock_in'
                                        ? 'Carga rápida'
                                        : ($quickKind === 'quick_retirement' ? 'Retiro rápido' : 'Captura rápida');
                                @endphp
                                <tr wire:key="pending-task-row-{{ $task->id }}">
                                    <td class="fw-semibold">{{ $task->id }}</td>
                                    <td class="min-w-0">
                                        <div class="d-flex align-items-center gap-2 flex-wrap min-w-0">
                                            <span class="text-truncate">{{ $task->type->label() }}</span>
                                            @if ($isQuickCapture)
                                                <x-ui.badge tone="info" variant="compact" :with-rail="false">{{ $quickLabel }}</x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <x-ui.badge :tone="$task->status->badgeTone()" variant="compact">
                                            {{ $task->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td>{{ $task->lines_count }}</td>
                                    <td class="min-w-0">
                                        <span class="text-truncate d-inline-block" style="max-width: 16rem;">
                                            {{ $task->creator->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $task->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a
                                            class="btn btn-sm btn-outline-secondary"
                                            href="{{ route('pending-tasks.show', $task->id) }}"
                                            aria-label="Ver tarea pendiente {{ $task->id }}"
                                        >
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-list-task"
                                                title="No hay tareas pendientes"
                                                description="Cuando generes tareas, aparecerán aquí para su seguimiento y procesamiento."
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
                    {{ $tasks->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
