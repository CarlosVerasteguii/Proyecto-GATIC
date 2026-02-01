@props([
    /** @var array<int, array{label: string, url?: string|null}> $items */
    'items' => [],
])

@php
    $safeItems = array_values(array_filter(
        is_array($items) ? $items : [],
        static fn ($item): bool => is_array($item) && isset($item['label']) && is_string($item['label']) && trim($item['label']) !== ''
    ));
@endphp

@if ($safeItems !== [])
    <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb mb-0">
            @foreach ($safeItems as $index => $item)
                @php
                    $isLast = $index === count($safeItems) - 1;
                    $label = trim((string) $item['label']);
                    $url = $item['url'] ?? null;
                @endphp

                @if ($isLast)
                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                @else
                    <li class="breadcrumb-item">
                        @if (is_string($url) && $url !== '')
                            <a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a>
                        @else
                            {{ $label }}
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

