<div class="card mt-3" wire:key="timeline-panel-{{ $entityType }}-{{ $entityId }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-1" aria-hidden="true"></i>Timeline</span>
        <span class="badge bg-secondary">{{ count($events) }}</span>
    </div>
    <div class="card-body">
        <x-ui.long-request target="toggleFilter,loadMore" />

        {{-- Filter chips --}}
        @if (count($this->availableFilters) > 1)
            <div class="d-flex flex-wrap gap-1 mb-3">
                @foreach ($this->availableFilters as $filter)
                    <button
                        type="button"
                        wire:click="toggleFilter('{{ $filter }}')"
                        aria-pressed="{{ in_array($filter, $activeFilters, true) ? 'true' : 'false' }}"
                        @class([
                            'btn btn-sm',
                            'btn-primary' => in_array($filter, $activeFilters, true),
                            'btn-outline-secondary' => ! in_array($filter, $activeFilters, true),
                        ])
                    >
                        {{ $filter }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Events list --}}
        @if (count($events) === 0)
            <p class="text-muted mb-0">Sin actividad registrada aún.</p>
        @else
            <div class="list-group list-group-flush">
                @foreach ($events as $event)
                    <div class="list-group-item px-0 py-2" wire:key="tl-{{ $event['source'] }}-{{ $event['source_id'] }}">
                        <div class="d-flex align-items-start gap-2">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 mt-1">
                                <i class="{{ $event['icon'] }} text-muted" aria-hidden="true" title="{{ $event['label'] }}"></i>
                            </div>

                            {{-- Content --}}
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                    <div>
                                        <span class="badge bg-light text-dark border me-1">{{ $event['label'] }}</span>
                                        <strong class="small">{{ $event['title'] }}</strong>
                                    </div>
                                    <small class="text-muted text-nowrap" title="{{ $event['occurred_at_human'] }}">
                                        {{ $event['occurred_at_diff'] }}
                                    </small>
                                </div>

                                @if ($event['summary'])
                                    <div class="small text-muted mt-1" style="white-space: pre-wrap; word-break: break-word;">{{ $event['summary'] }}</div>
                                @endif

                                <details class="mt-1">
                                    <summary class="small text-decoration-none">Detalles</summary>
                                    @if (! empty($event['meta']))
                                        <dl class="row small text-muted mt-2 mb-0">
                                            @foreach ($event['meta'] as $k => $v)
                                                <dt class="col-sm-3 mb-1">{{ $k }}</dt>
                                                <dd class="col-sm-9 mb-1" style="white-space: pre-wrap; word-break: break-word;">
                                                    {{ is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}
                                                </dd>
                                            @endforeach
                                        </dl>
                                    @else
                                        <div class="small text-muted mt-2">Sin detalles adicionales.</div>
                                    @endif
                                </details>

                                <div class="d-flex align-items-center gap-2 mt-1">
                                    @if ($event['actor_name'])
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $event['actor_name'] }}
                                        </small>
                                    @endif
                                    @if ($event['route_url'])
                                        <a href="{{ $event['route_url'] }}" class="small text-decoration-none">
                                            <i class="bi bi-download me-1" aria-hidden="true"></i>Descargar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Load more --}}
            @if ($hasMore)
                <div class="text-center mt-3">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:target="loadMore"
                    >
                        <span wire:loading.remove wire:target="loadMore">Cargar más</span>
                        <span wire:loading wire:target="loadMore">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Cargando...
                        </span>
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
