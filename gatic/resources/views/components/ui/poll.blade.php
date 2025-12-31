@props([
    'method',
    'intervalS' => null,
    'visible' => true,
    'enabled' => null,
])

@php
    $enabled = is_null($enabled)
        ? (bool) config('gatic.ui.polling.enabled', true)
        : (filter_var($enabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $enabled);

    $visible = filter_var($visible, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $visible;

    $defaultIntervalS = (int) config('gatic.ui.polling.badges_interval_s', 15);
    $intervalS = is_null($intervalS) ? $defaultIntervalS : (int) $intervalS;

    if ($intervalS <= 0) {
        $intervalS = $defaultIntervalS;
    }

    if ($intervalS <= 0 || ! is_string($method) || trim($method) === '') {
        $enabled = false;
    }

    $pollAttribute = null;
    if ($enabled) {
        $pollVariant = $visible ? 'wire:poll.visible' : 'wire:poll';
        $pollAttribute = sprintf('%s.%ss', $pollVariant, $intervalS);
    }
@endphp

<div {{ $pollAttribute ? $attributes->merge([$pollAttribute => $method]) : $attributes }}>
    {{ $slot }}
</div>
