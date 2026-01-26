{{--
    Toolbar Component
    -----------------
    Consistent toolbar for list views with search, filters, and actions.

    Usage:
        <x-ui.toolbar title="Productos">
            <x-slot:actions>
                <a href="..." class="btn btn-sm btn-primary">Nuevo</a>
            </x-slot:actions>

            <x-slot:search>
                <input type="text" ... />
            </x-slot:search>

            <x-slot:filters>
                <select>...</select>
                <select>...</select>
            </x-slot:filters>

            <x-slot:clearFilters>
                <button>Limpiar</button>
            </x-slot:clearFilters>
        </x-ui.toolbar>

    Props:
        - title (string): Main title shown in header
        - subtitle (string, optional): Secondary text below title
        - filtersCollapsible (bool): Enable collapsible filters on mobile (default: true)
        - filterId (string): Unique ID for collapse element (default: 'toolbar-filters')
--}}
@props([
    'title' => '',
    'subtitle' => null,
    'filtersCollapsible' => true,
    'filterId' => 'toolbar-filters',
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    {{-- Header with title and primary actions --}}
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex flex-column">
            <span class="fw-medium">{{ $title }}</span>
            @if($subtitle)
                <small class="text-muted">{{ $subtitle }}</small>
            @endif
        </div>

        @if(isset($actions))
            <div class="d-flex gap-2 align-items-center">
                {{ $actions }}
            </div>
        @endif
    </div>

    {{-- Body with search and filters --}}
    <div class="card-body">
        @if(isset($search) || isset($filters))
            <div class="toolbar-filters mb-3">
                {{-- Desktop layout: all in row --}}
                <div class="row g-3 align-items-end">
                    {{-- Search (always visible) --}}
                    @if(isset($search))
                        <div class="col-12 col-md-3">
                            {{ $search }}
                        </div>
                    @endif

                    {{-- Filters (collapsible on mobile if enabled) --}}
                    @if(isset($filters))
                        @if($filtersCollapsible)
                            {{-- Mobile: collapsible --}}
                            <div class="col-12 d-md-none">
                                <button
                                    class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $filterId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $filterId }}"
                                >
                                    <span><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filtros</span>
                                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                </button>
                            </div>

                            {{-- Mobile: collapsed filters --}}
                            <div class="col-12 collapse d-md-none" id="{{ $filterId }}">
                                <div class="row g-3 pt-2">
                                    {{ $filters }}
                                </div>
                            </div>

                            {{-- Desktop: visible filters --}}
                            <div class="d-none d-md-contents">
                                {{ $filters }}
                            </div>
                        @else
                            {{-- Non-collapsible: always visible --}}
                            {{ $filters }}
                        @endif
                    @endif

                    {{-- Clear filters button --}}
                    @if(isset($clearFilters))
                        <div class="col-12 col-md-auto">
                            {{ $clearFilters }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Main content slot --}}
        {{ $slot }}
    </div>
</div>
