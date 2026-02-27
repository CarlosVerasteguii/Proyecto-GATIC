<div class="container position-relative ops-page ops-pending-tasks-page">
    <x-ui.long-request />

    @php
        $hasFilters = $this->hasActiveFilters();
        $resultsCount = $tasks->total();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Tareas pendientes"
                subtitle="Procesa movimientos en lote con locks para evitar duplicados."
                filterId="pending-tasks-filters"
                class="ops-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Tareas pendientes', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <span class="dash-chip">
                        Resultados <strong>{{ number_format($resultsCount) }}</strong>
                    </span>
                    <x-ui.column-manager table="pending-tasks" />
                    <livewire:pending-tasks.quick-stock-in :key="'quick-stock-in-index'" />
                    <livewire:pending-tasks.quick-retirement :key="'quick-retirement-index'" />
                    <livewire:pending-tasks.pending-task-opener :key="'pending-task-opener-index'" />
                    <a class="btn btn-sm btn-primary" href="{{ route('pending-tasks.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva tarea
                    </a>
                </x-slot:actions>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="filter-status" class="form-label">Estado</label>
                        <select
                            id="filter-status"
                            name="status"
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
                    <div class="col-12 col-md-4">
                        <label for="filter-type" class="form-label">Tipo de operación</label>
                        <select
                            id="filter-type"
                            name="type"
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
                    @php
                        $clearHidden = ! $hasFilters;
                    @endphp
                    <button
                        type="button"
                        class="btn btn-outline-secondary{{ $clearHidden ? ' invisible' : '' }}"
                        wire:click="clearFilters"
                        wire:loading.attr="disabled"
                        wire:target="clearFilters"
                        aria-label="Limpiar filtros"
                        title="Limpiar filtros"
                        @if ($clearHidden) disabled aria-hidden="true" tabindex="-1" @endif
                    >
                        <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                        Limpiar
                    </button>
                </x-slot:clearFilters>

                <div class="small text-body-secondary mb-2" aria-live="polite">
                    <span>Mostrando {{ number_format($resultsCount) }} resultado{{ $resultsCount === 1 ? '' : 's' }}.</span>
                    <span
                        class="ms-2 align-items-center gap-2"
                        wire:loading.inline-flex
                        wire:target="statusFilter,typeFilter,clearFilters"
                    >
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        Actualizando…
                    </span>
                </div>

                <div
                    class="table-responsive border rounded-3"
                    wire:loading.class="opacity-50 pe-none"
                    wire:target="statusFilter,typeFilter,clearFilters"
                >
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head ops-table" data-column-table="pending-tasks">
                        <thead>
                            <tr>
                                <th data-column-key="id" data-column-required="true">ID</th>
                                <th data-column-key="type">Tipo</th>
                                <th data-column-key="status">Estado</th>
                                <th data-column-key="lines">Renglones</th>
                                <th data-column-key="creator">Creador</th>
                                <th data-column-key="created_at">Fecha</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end" style="width: 1%;">Acciones</th>
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
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ $task->type->label() }}</span>
                                            @if ($isQuickCapture)
                                                <span class="badge text-bg-info">{{ $quickLabel }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="{{ $task->status->badgeClass() }}">
                                            <span class="ops-status-chip__dot" aria-hidden="true"></span>
                                            <span>{{ $task->status->label() }}</span>
                                        </span>
                                    </td>
                                    <td>{{ $task->lines_count }}</td>
                                    <td>{{ $task->creator->name ?? '-' }}</td>
                                    <td>{{ $task->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a
                                            class="btn btn-outline-secondary ops-action-btn"
                                            href="{{ route('pending-tasks.show', $task->id) }}"
                                            aria-label="Ver tarea #{{ $task->id }}"
                                            title="Ver"
                                        >
                                            <i class="bi bi-eye" aria-hidden="true"></i>
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
                                                description="Crea una tarea para procesar movimientos en lote."
                                                compact
                                            >
                                                <a class="btn btn-sm btn-primary" href="{{ route('pending-tasks.create') }}">
                                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva tarea
                                                </a>
                                            </x-ui.empty-state>
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
