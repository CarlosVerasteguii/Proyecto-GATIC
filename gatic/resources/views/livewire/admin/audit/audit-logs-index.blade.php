<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard') }}">
                    Volver al Dashboard
                </a>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <span>Registro de Auditoría</span>
                        <x-ui.freshness-indicator :updated-at="now()" />
                    </div>
                    @if($this->hasActiveFilters())
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                            Limpiar filtros
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label small mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" wire:model.live.debounce.300ms="dateFrom">
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label small mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" wire:model.live.debounce.300ms="dateTo">
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label small mb-1">Actor</label>
                            <select class="form-select form-select-sm" wire:model.live="actorId">
                                <option value="">Todos</option>
                                @foreach($actors as $actor)
                                    <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small mb-1">Acción</label>
                            <select class="form-select form-select-sm" wire:model.live="action">
                                <option value="">Todas</option>
                                @foreach($actions as $actionValue)
                                    <option value="{{ $actionValue }}">{{ $actionLabels[$actionValue] ?? $actionValue }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small mb-1">Entidad</label>
                            <select class="form-select form-select-sm" wire:model.live="subjectType">
                                <option value="">Todas</option>
                                @foreach($subjectTypes as $type)
                                    <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 140px;">Fecha</th>
                                    <th style="width: 120px;">Actor</th>
                                    <th>Acción</th>
                                    <th style="width: 140px;">Entidad</th>
                                    <th style="width: 80px;">ID</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="text-nowrap small">
                                            {{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}
                                        </td>
                                        <td class="small">
                                            {{ $log->actor?->name ?? '-' }}
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-secondary">{{ $log->action_label }}</span>
                                        </td>
                                        <td class="small text-nowrap">
                                            {{ $log->subject_type_short }}
                                        </td>
                                        <td class="small">
                                            {{ $log->subject_id }}
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="showDetail({{ $log->id }})">
                                                Ver
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">
                                            No hay registros de auditoría.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($selectedLog)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle de Auditoría #{{ $selectedLog->id }}</h5>
                        <button type="button" class="btn-close" wire:click="closeDetail"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Fecha</dt>
                            <dd class="col-sm-9">{{ $selectedLog->created_at?->format('Y-m-d H:i:s') ?? '-' }}</dd>

                            <dt class="col-sm-3">Actor</dt>
                            <dd class="col-sm-9">{{ $selectedLog->actor?->name ?? '-' }} (ID: {{ $selectedLog->actor_user_id ?? '-' }})</dd>

                            <dt class="col-sm-3">Acción</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-secondary">{{ $selectedLog->action_label }}</span>
                                <code class="ms-2 small">{{ $selectedLog->action }}</code>
                            </dd>

                            <dt class="col-sm-3">Entidad</dt>
                            <dd class="col-sm-9">
                                {{ $selectedLog->subject_type_short }} (ID: {{ $selectedLog->subject_id }})
                                <br>
                                <code class="small text-muted">{{ $selectedLog->subject_type }}</code>
                            </dd>

                            @if($selectedLog->context)
                                <dt class="col-sm-3">Contexto</dt>
                                <dd class="col-sm-9">
                                    <pre class="bg-light p-2 rounded small mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($selectedLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </dd>
                            @endif
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetail">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
