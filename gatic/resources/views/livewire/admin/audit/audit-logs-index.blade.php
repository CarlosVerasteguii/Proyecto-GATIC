<div class="container position-relative admin-audit-page">
    <x-ui.long-request />

    @php
        $resultsCount = $logs->total();
        $hasFilters = $this->hasActiveFilters();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Registro de Auditoría"
                subtitle="Reconstruye acciones administrativas con filtros por actor, fecha, acción y entidad."
                filterId="admin-audit-filters"
                :filtersCollapsible="false"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Administración', 'url' => route('admin.audit.index')],
                        ['label' => 'Auditoría', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Resultados <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>
                    @if ($hasFilters)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Filtros activos
                        </x-ui.badge>
                    @endif
                </x-slot:actions>

                <x-slot:filters>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="audit-date-from" class="form-label">Desde</label>
                        <input
                            id="audit-date-from"
                            type="date"
                            class="form-control"
                            wire:model.live.debounce.300ms="dateFrom"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="audit-date-to" class="form-label">Hasta</label>
                        <input
                            id="audit-date-to"
                            type="date"
                            class="form-control"
                            wire:model.live.debounce.300ms="dateTo"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <label for="audit-actor" class="form-label">Actor</label>
                        <select
                            id="audit-actor"
                            class="form-select"
                            wire:model.live="actorId"
                        >
                            <option value="">Todos</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="audit-action" class="form-label">Acción</label>
                        <select
                            id="audit-action"
                            class="form-select"
                            wire:model.live="action"
                        >
                            <option value="">Todas</option>
                            @foreach ($actions as $actionValue)
                                <option value="{{ $actionValue }}">{{ $actionLabels[$actionValue] ?? $actionValue }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="audit-subject-type" class="form-label">Entidad</label>
                        <select
                            id="audit-subject-type"
                            class="form-select"
                            wire:model.live="subjectType"
                        >
                            <option value="">Todas</option>
                            @foreach ($subjectTypes as $type)
                                <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} registro{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Actor</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr wire:key="audit-log-{{ $log->id }}">
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">{{ $log->created_at?->format('d/m/Y') ?? '—' }}</div>
                                        <div class="small text-body-secondary">{{ $log->created_at?->format('H:i:s') ?? '—' }}</div>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $log->actor?->name ?? 'Sistema' }}</div>
                                        <div class="small text-body-secondary text-truncate">
                                            {{ $log->actor_user_id ? 'ID '.$log->actor_user_id : 'Sin actor asociado' }}
                                        </div>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="d-flex flex-column gap-1 min-w-0">
                                            <div>
                                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">{{ $log->action_label }}</x-ui.badge>
                                            </div>
                                            <code class="small text-body-secondary text-truncate">{{ $log->action }}</code>
                                        </div>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $log->subject_type_short }}</div>
                                        <div class="small text-body-secondary text-truncate">
                                            ID {{ $log->subject_id }} · {{ $log->subject_type }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            id="audit-log-detail-trigger-{{ $log->id }}"
                                            wire:click="showDetail({{ $log->id }})"
                                            aria-label="Ver detalle del registro de auditoría {{ $log->id }}"
                                        >
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i>Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-journal-text"
                                                title="No hay registros de auditoría"
                                                description="Cuando existan eventos auditables, aparecerán aquí para soporte y diagnóstico."
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
                    {{ $logs->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>

    @if ($selectedLog)
        @php
            $detailModalId = 'audit-log-detail-modal-'.$selectedLog->id;
            $detailTitleId = 'audit-log-detail-title-'.$selectedLog->id;
        @endphp

        <div
            id="{{ $detailModalId }}"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $detailTitleId }}"
            wire:keydown.escape.window="closeDetail"
            style="background-color: rgba(0,0,0,0.5);"
            data-manual-dialog
            data-manual-dialog-restore-id="audit-log-detail-trigger-{{ $selectedLog->id }}"
        >
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 mb-1" id="{{ $detailTitleId }}">Detalle de Auditoría #{{ $selectedLog->id }}</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">{{ $selectedLog->action_label }}</x-ui.badge>
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">{{ $selectedLog->subject_type_short }}</x-ui.badge>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Cerrar detalle de auditoría"
                            wire:click="closeDetail"
                            data-manual-dialog-close
                            data-manual-dialog-initial-focus
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-lg-5">
                                <x-ui.section-card title="Resumen" icon="bi-journal-text" class="h-100">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Fecha</dt>
                                        <dd class="col-sm-8">{{ $selectedLog->created_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                                        <dt class="col-sm-4">Actor</dt>
                                        <dd class="col-sm-8">
                                            <div>{{ $selectedLog->actor?->name ?? 'Sistema' }}</div>
                                            <div class="small text-body-secondary">
                                                {{ $selectedLog->actor_user_id ? 'ID '.$selectedLog->actor_user_id : 'Sin actor asociado' }}
                                            </div>
                                        </dd>

                                        <dt class="col-sm-4">Acción</dt>
                                        <dd class="col-sm-8">
                                            <div>{{ $selectedLog->action_label }}</div>
                                            <code class="small text-body-secondary">{{ $selectedLog->action }}</code>
                                        </dd>

                                        <dt class="col-sm-4">Entidad</dt>
                                        <dd class="col-sm-8">
                                            <div>{{ $selectedLog->subject_type_short }} #{{ $selectedLog->subject_id }}</div>
                                            <code class="small text-body-secondary">{{ $selectedLog->subject_type }}</code>
                                        </dd>
                                    </dl>
                                </x-ui.section-card>
                            </div>

                            <div class="col-12 col-lg-7">
                                <x-ui.section-card title="Contexto técnico" icon="bi-braces" class="h-100" bodyClass="p-0">
                                    @if ($selectedLog->context)
                                        <pre class="mb-0 p-3 small overflow-auto bg-body-tertiary" style="max-height: 28rem;">{{ json_encode($selectedLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <div class="p-3 text-body-secondary small">
                                            Este evento no registró contexto adicional.
                                        </div>
                                    @endif
                                </x-ui.section-card>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetail" data-manual-dialog-close>Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
