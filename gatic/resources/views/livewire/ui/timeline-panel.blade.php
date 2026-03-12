@php($loadedEventsCount = count($events))

<x-ui.section-card
    title="Timeline"
    subtitle="Historial consolidado de movimientos, notas, adjuntos y eventos de sistema."
    icon="bi-clock-history"
    bodyClass="trace-panel__body"
    class="trace-panel trace-panel--timeline h-100"
    wire:key="timeline-panel-{{ $entityType }}-{{ $entityId }}"
>
    <x-slot:actions>
        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
            {{ $loadedEventsCount }}
        </x-ui.badge>
        @if (count($activeFilters) > 0)
            <x-ui.badge tone="info" variant="compact" :with-rail="false">
                {{ count($activeFilters) }} filtro{{ count($activeFilters) === 1 ? '' : 's' }}
            </x-ui.badge>
        @endif
    </x-slot:actions>

    <x-ui.long-request target="toggleFilter,loadMore" />

    <div class="trace-panel__intro">
        <div>
            <div class="trace-panel__eyebrow">Trazabilidad</div>
            <p class="trace-panel__hint mb-0">
                Sigue el contexto operativo más reciente sin salir de la ficha actual.
            </p>
        </div>
        @if ($loadedEventsCount > 0)
            <div class="trace-panel__metric">
                {{ $hasMore ? 'Mostrando una vista parcial del historial.' : 'Historial cargado en esta vista.' }}
            </div>
        @endif
    </div>

    @if (count($this->availableFilters) > 1)
        <div class="trace-panel__filters">
            <div class="trace-panel__eyebrow">Filtrar por categoría</div>
            <div class="trace-panel__filter-row" role="group" aria-label="Filtrar timeline por categoría">
                @foreach ($this->availableFilters as $filter)
                    @php($isActive = in_array($filter, $activeFilters, true))
                    <x-ui.badge
                        as="button"
                        :tone="$isActive ? 'primary' : 'neutral'"
                        variant="compact"
                        :with-rail="false"
                        class="trace-panel__filter"
                        wire:click="toggleFilter('{{ $filter }}')"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    >
                        {{ $filter }}
                    </x-ui.badge>
                @endforeach
            </div>
        </div>
    @endif

    @if ($loadedEventsCount === 0)
        <div class="trace-panel__empty">
            <x-ui.empty-state
                icon="bi-clock-history"
                title="Sin actividad registrada"
                description="Sin actividad registrada aún."
                compact
            />
        </div>
    @else
        <ol class="trace-panel__list list-unstyled mb-0">
            @foreach ($events as $event)
                <li class="trace-event" wire:key="tl-{{ $event['source'] }}-{{ $event['source_id'] }}">
                    <div class="trace-event__icon">
                        <i class="{{ $event['icon'] }}" aria-hidden="true" title="{{ $event['label'] }}"></i>
                    </div>

                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    {{ $event['label'] }}
                                </x-ui.badge>
                                <h3 class="trace-event__title h6 mb-0">{{ $event['title'] }}</h3>
                            </div>

                            @if ($event['summary'])
                                <p class="trace-event__summary mb-0 mt-2">{{ $event['summary'] }}</p>
                            @endif
                        </div>

                        <div class="trace-panel__timestamp text-nowrap text-end" title="{{ $event['occurred_at_human'] }}">
                            <div>{{ $event['occurred_at_human'] }}</div>
                            <div>{{ $event['occurred_at_diff'] }}</div>
                        </div>
                    </div>

                    @if ($event['actor_name'] || $event['route_url'])
                        <div class="trace-panel__meta d-flex align-items-center gap-3 flex-wrap mt-3">
                            @if ($event['actor_name'])
                                <span>
                                    <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $event['actor_name'] }}
                                </span>
                            @endif
                            @if ($event['route_url'])
                                <a href="{{ $event['route_url'] }}" class="text-decoration-none">
                                    <i class="bi bi-download me-1" aria-hidden="true"></i>Descargar
                                </a>
                            @endif
                        </div>
                    @endif

                    <details class="trace-event__details mt-3">
                        <summary class="small">Detalles</summary>
                        @if (! empty($event['meta']))
                            <dl class="row small mt-2 mb-0">
                                @foreach ($event['meta'] as $k => $v)
                                    <dt class="col-sm-3 mb-1">{{ $k }}</dt>
                                    <dd class="col-sm-9 mb-1 trace-event__summary">
                                        {{ is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}
                                    </dd>
                                @endforeach
                            </dl>
                        @else
                            <div class="trace-panel__meta mt-2">Sin detalles adicionales.</div>
                        @endif
                    </details>
                </li>
            @endforeach
        </ol>

        @if ($hasMore)
            <div class="text-center">
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
                        Cargando…
                    </span>
                </button>
            </div>
        @endif
    @endif
</x-ui.section-card>
