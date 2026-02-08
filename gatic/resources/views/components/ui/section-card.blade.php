@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null, // bootstrap-icons class without "bi " prefix
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => 'card dash-section']) }}>
    @if (is_string($title) && $title !== '')
        <div class="card-header dash-section-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                @if (is_string($icon) && $icon !== '')
                    <i class="bi {{ $icon }} text-secondary" aria-hidden="true"></i>
                @endif
                <div class="dash-section-title text-truncate">
                    {{ $title }}
                </div>
                @if (is_string($subtitle) && $subtitle !== '')
                    <small class="dash-section-subtitle text-truncate">{{ $subtitle }}</small>
                @endif
            </div>

            @isset($actions)
                <div class="d-flex align-items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="card-body dash-section-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
