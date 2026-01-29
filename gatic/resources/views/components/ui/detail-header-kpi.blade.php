{{--
    Detail Header KPI Component
    ---------------------------
    Single KPI item for use inside detail-header.

    Usage:
        <x-ui.detail-header-kpi label="Total" :value="42" />
        <x-ui.detail-header-kpi label="Disponibles" :value="$available" variant="success" />

    Props:
        - label (string): KPI label text
        - value (string|int): KPI value
        - variant (string, optional): Color variant (success, warning, danger, info)
--}}
@props([
    'label' => '',
    'value' => 0,
    'variant' => null,
])

@php
    $valueClass = match($variant) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'info' => 'text-info',
        default => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'detail-header-kpi text-center text-lg-start']) }}>
    <div class="text-muted small text-uppercase fw-medium" style="font-size: 0.7rem; letter-spacing: 0.05em;">
        {{ $label }}
    </div>
    <div class="fs-4 fw-bold {{ $valueClass }}" style="line-height: 1.2;">
        {{ $value }}
    </div>
</div>
