@props([
    'variant' => 'lines',
    'lines' => 3,
    'width' => null,
    'height' => null,
])

@php
    $resolvedLines = max(1, (int) $lines);
    $styleParts = [];

    if (is_string($width) && $width !== '') {
        $styleParts[] = "width: {$width}";
    }

    if (is_string($height) && $height !== '') {
        $styleParts[] = "height: {$height}";
    }

    $style = implode('; ', $styleParts);
@endphp

@if ($variant === 'block')
    <div {{ $attributes->merge(['class' => 'placeholder-glow']) }}>
        <span class="placeholder col-12" @if ($style !== '') style="{{ $style }}" @endif></span>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'placeholder-glow']) }}>
        @for ($i = 0; $i < $resolvedLines; $i++)
            <span class="placeholder col-12 mb-2" @if ($style !== '') style="{{ $style }}" @endif></span>
        @endfor
    </div>
@endif

