@props([
    'updatedAt' => null,
])

@php
    $updatedAtMs = null;

    if ($updatedAt instanceof \Carbon\CarbonInterface) {
        $updatedAtMs = method_exists($updatedAt, 'getTimestampMs')
            ? $updatedAt->getTimestampMs()
            : ($updatedAt->getTimestamp() * 1000);
    } elseif (is_int($updatedAt)) {
        $updatedAtMs = $updatedAt;
    } elseif (is_string($updatedAt) && $updatedAt !== '') {
        $parsed = strtotime($updatedAt);
        if ($parsed !== false) {
            $updatedAtMs = $parsed * 1000;
        }
    }

    $updatedAtMs ??= now()->getTimestamp() * 1000;
@endphp

<span {{ $attributes->merge(['class' => 'small text-muted']) }}>
    Actualizado hace
    <span data-gatic-freshness data-updated-at-ms="{{ (int) $updatedAtMs }}">0s</span>
</span>

