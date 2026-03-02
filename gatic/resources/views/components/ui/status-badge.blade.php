{{--
    Status Badge Component
    ----------------------
    Renders a consistent status badge based on asset status values.

    Usage:
        <x-ui.status-badge :status="$asset->status" />
        <x-ui.status-badge status="Disponible" />
        <x-ui.status-badge :status="$status" solid />

    Props:
        - status (string): One of: Disponible, Prestado, Asignado, Pendiente de Retiro, Retirado
        - solid (bool): Use solid background variant for higher emphasis
        - icon (bool): Show status icon (default: true)
--}}
@props([
    'status' => '',
    'solid' => false,
    'icon' => true,
])

@php
    use App\Models\Asset;

    $statusMap = [
        Asset::STATUS_AVAILABLE => [
            'class' => 'available',
            'tone' => 'status-available',
            'icon' => 'bi-check-circle-fill',
        ],
        Asset::STATUS_LOANED => [
            'class' => 'loaned',
            'tone' => 'status-loaned',
            'icon' => 'bi-arrow-left-right',
        ],
        Asset::STATUS_ASSIGNED => [
            'class' => 'assigned',
            'tone' => 'status-assigned',
            'icon' => 'bi-person-fill',
        ],
        Asset::STATUS_PENDING_RETIREMENT => [
            'class' => 'pending',
            'tone' => 'status-pending',
            'icon' => 'bi-clock-fill',
        ],
        Asset::STATUS_RETIRED => [
            'class' => 'retired',
            'tone' => 'status-retired',
            'icon' => 'bi-x-circle-fill',
        ],
    ];

    $config = $statusMap[$status] ?? [
        'class' => 'secondary',
        'tone' => 'secondary',
        'icon' => 'bi-question-circle',
    ];

    $classes = collect([
        'status-badge',
        'status-badge--' . $config['class'],
        $solid ? 'status-badge--solid' : null,
    ])->filter()->implode(' ');
@endphp

<x-ui.badge
    :tone="$config['tone']"
    :variant="$solid ? 'solid' : 'default'"
    :icon="$icon ? $config['icon'] : false"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $status }}
</x-ui.badge>
