{{--
    Empty State Component
    ---------------------
    Displays a helpful message when no data is available, with optional action.

    Usage:
        {{-- No data --}}
        <x-ui.empty-state
            icon="bi-box"
            title="No hay productos"
            description="Crea tu primer producto para comenzar."
        >
            <a href="..." class="btn btn-primary">Crear producto</a>
        </x-ui.empty-state>

        {{-- No filter results --}}
        <x-ui.empty-state
            variant="filter"
            title="Sin resultados"
            description="Intenta ajustar los filtros."
        />

    Props:
        - icon (string): Bootstrap icon class (default: bi-inbox)
        - title (string): Main heading
        - description (string): Supporting text
        - variant (string): 'empty' (default) or 'filter' (no results from filtering)
        - compact (bool): Use smaller padding for table cells (default: false)
--}}
@props([
    'icon' => 'bi-inbox',
    'title' => 'Sin datos',
    'description' => null,
    'variant' => 'empty',
    'compact' => false,
])

@php
    $isFilter = $variant === 'filter';

    // Default icon based on variant
    if ($icon === 'bi-inbox' && $isFilter) {
        $icon = 'bi-search';
    }

    // Default title/description based on variant
    if ($title === 'Sin datos' && $isFilter) {
        $title = 'Sin resultados';
    }

    if ($description === null) {
        $description = $isFilter
            ? 'No se encontraron coincidencias. Intenta ajustar los filtros.'
            : 'No hay elementos para mostrar.';
    }
@endphp

<div {{ $attributes->merge(['class' => 'empty-state text-center ' . ($compact ? 'py-4' : 'py-5')]) }}>
    <div class="empty-state__icon mb-3">
        <i class="bi {{ $icon }} text-muted" style="font-size: {{ $compact ? '2rem' : '3rem' }}" aria-hidden="true"></i>
    </div>

    <h5 class="empty-state__title text-muted fw-medium mb-2">{{ $title }}</h5>

    @if($description)
        <p class="empty-state__description text-muted small mb-3" style="max-width: 24rem; margin-inline: auto;">
            {{ $description }}
        </p>
    @endif

    @if($slot->isNotEmpty())
        <div class="empty-state__action mt-3">
            {{ $slot }}
        </div>
    @endif
</div>
