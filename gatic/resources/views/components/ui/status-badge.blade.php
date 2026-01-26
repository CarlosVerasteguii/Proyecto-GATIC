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
            'icon' => 'bi-check-circle-fill',
        ],
        Asset::STATUS_LOANED => [
            'class' => 'loaned',
            'icon' => 'bi-arrow-left-right',
        ],
        Asset::STATUS_ASSIGNED => [
            'class' => 'assigned',
            'icon' => 'bi-person-fill',
        ],
        Asset::STATUS_PENDING_RETIREMENT => [
            'class' => 'pending',
            'icon' => 'bi-clock-fill',
        ],
        Asset::STATUS_RETIRED => [
            'class' => 'retired',
            'icon' => 'bi-x-circle-fill',
        ],
    ];

    $config = $statusMap[$status] ?? [
        'class' => 'secondary',
        'icon' => 'bi-question-circle',
    ];

    $classes = collect([
        'status-badge',
        'status-badge--' . $config['class'],
        $solid ? 'status-badge--solid' : null,
    ])->filter()->implode(' ');
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
    @endif
    <span>{{ $status }}</span>
</span>
