@props([
    'label' => '',
    'value' => 0,
    'description' => null,
    'variant' => null, // success|warning|danger|info|primary|secondary
    'icon' => null, // bootstrap-icons class without "bi " prefix (e.g. "bi-activity")
    'href' => null,
    'cta' => 'Ver lista',
    'ctaTestid' => null,
    'testid' => null,
])

@php
    $variantClass = is_string($variant) && $variant !== '' ? "dash-variant-{$variant}" : null;
    $valueTextClass = is_string($variant) && $variant !== '' ? "text-{$variant}" : '';
    $btnVariant = is_string($variant) && $variant !== '' ? $variant : 'secondary';
    $btnClass = "btn btn-sm btn-outline-{$btnVariant}";
@endphp

<div {{ $attributes->merge(['class' => 'card dash-kpi h-100'])->class([$variantClass]) }}>
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="flex-grow-1">
                <div class="dash-kpi-label">{{ $label }}</div>
                @if (is_string($description) && $description !== '')
                    <div class="dash-kpi-description">{{ $description }}</div>
                @endif
            </div>

            @if (is_string($icon) && $icon !== '')
                <div class="dash-kpi-icon" aria-hidden="true">
                    <i class="bi {{ $icon }}"></i>
                </div>
            @endif
        </div>

        <div class="dash-kpi-value {{ $valueTextClass }}" @if (is_string($testid) && $testid !== '') data-testid="{{ $testid }}" @endif>
            {{ $value }}
        </div>

        {{ $slot }}

        @if (is_string($href) && $href !== '')
            <div class="mt-3">
                <a
                    href="{{ $href }}"
                    class="{{ $btnClass }}"
                    @if (is_string($ctaTestid) && $ctaTestid !== '') data-testid="{{ $ctaTestid }}" @endif
                >
                    {{ $cta }}
                    <i class="bi bi-box-arrow-up-right small ms-1" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>
</div>
