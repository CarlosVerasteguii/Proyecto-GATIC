@props([
    'tone' => 'neutral',
    'variant' => 'default',
    'size' => null, // alias for variant (default|compact)
    'icon' => false, // Bootstrap Icons class (e.g. "bi-check-circle-fill") or false
    'ariaLive' => false, // "polite" only when content changes async; otherwise false
    'withRail' => null, // null => default per variant; true/false to force
    'as' => 'span', // span|a|button
])

@php
    $tone = is_string($tone) ? trim($tone) : 'neutral';
    if ($tone === '') {
        $tone = 'neutral';
    }
    $tone = strtolower($tone);
    $tone = preg_replace('/[^a-z0-9\\-]/', '', $tone) ?? 'neutral';
    if ($tone === '') {
        $tone = 'neutral';
    }

    $variant = is_string($variant) ? trim($variant) : 'default';
    if ($variant === '') {
        $variant = 'default';
    }

    if (is_string($size) && $size !== '') {
        $size = trim($size);
        if (in_array($size, ['default', 'compact'], true)) {
            $variant = $size;
        }
    }

    $allowedVariants = ['default', 'compact', 'solid'];
    if (! in_array($variant, $allowedVariants, true)) {
        $variant = 'default';
    }

    $allowedTags = ['span', 'a', 'button'];
    $tag = is_string($as) ? strtolower(trim($as)) : 'span';
    if (! in_array($tag, $allowedTags, true)) {
        $tag = 'span';
    }

    $withRail = is_null($withRail)
        ? ($variant !== 'solid')
        : (bool) $withRail;

    $isInteractive = in_array($tag, ['a', 'button'], true)
        || $attributes->has('href')
        || ($attributes->get('role') === 'button');

    $classes = collect([
        'gatic-badge',
        'gatic-badge--tone-' . $tone,
        'gatic-badge--variant-' . $variant,
        $withRail ? null : 'gatic-badge--no-rail',
        $isInteractive ? 'gatic-badge--interactive' : null,
    ])->filter()->implode(' ');

    $ariaLive = is_string($ariaLive) ? trim($ariaLive) : $ariaLive;
    $ariaAttributes = [];
    if (in_array($ariaLive, ['polite'], true)) {
        $ariaAttributes['role'] = 'status';
        $ariaAttributes['aria-live'] = $ariaLive;
    }

    $extraAttributes = [];
    if ($tag === 'button' && ! $attributes->has('type')) {
        $extraAttributes['type'] = 'button';
    }
@endphp

<{{ $tag }} {{ $attributes->merge($extraAttributes)->merge($ariaAttributes)->merge(['class' => $classes]) }}>
    @if(is_string($icon) && trim($icon) !== '')
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
