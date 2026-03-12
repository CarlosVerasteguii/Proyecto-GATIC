<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'category', 'brand', 'availability', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header :title="$product?->name ?? 'Producto'" :subtitle="$headerSubtitle">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Productos', 'url' => route('inventory.products.index', $returnQuery)],
                        ['label' => $product?->name ?? 'Producto', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    @foreach ($statusHighlights as $highlight)
                        <x-ui.badge :tone="$highlight['tone']" variant="compact" :with-rail="false">
                            {{ $highlight['label'] }}
                        </x-ui.badge>
                    @endforeach
                </x-slot:status>

                <x-slot:kpis>
                    @foreach ($headerMetrics as $metric)
                        <x-ui.detail-header-kpi
                            :label="$metric['label']"
                            :value="$metric['value']"
                            :variant="$metric['variant']"
                        />
                    @endforeach
                </x-slot:kpis>

                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.index', $returnQuery) }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver
                    </a>

                    @if ($productIsSerialized)
                        <a
                            class="btn btn-sm btn-primary"
                            href="{{ route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery) }}"
                        >
                            <i class="bi bi-hdd-stack me-1" aria-hidden="true"></i>
                            Ver activos
                        </a>
                    @else
                        <a
                            class="btn btn-sm btn-outline-info"
                            href="{{ route('inventory.products.kardex', ['product' => $product->id] + $returnQuery) }}"
                        >
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                            Ver kardex
                        </a>

                        @can('inventory.manage')
                            <a
                                class="btn btn-sm btn-primary"
                                href="{{ route('inventory.products.movements.quantity', ['product' => $product->id]) }}"
                            >
                                <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                                Registrar movimiento
                            </a>
                        @endcan

                        @can('admin-only')
                            <a
                                class="btn btn-sm btn-warning"
                                href="{{ route('inventory.products.adjust', ['product' => $product->id] + $returnQuery) }}"
                            >
                                Ajustar inventario
                            </a>
                        @endcan
                    @endif
                </x-slot:actions>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Resumen del producto"
                subtitle="Identifica rápidamente cómo opera este producto dentro del inventario y qué catálogos lo describen."
                icon="bi-box-seam"
                class="mb-4"
            >
                <div class="row g-3">
                    @foreach ($overviewCards as $card)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $card['label'] }}</div>
                                @if ($card['href'])
                                    <a href="{{ $card['href'] }}" class="fw-semibold mt-2 d-inline-block text-decoration-none">
                                        {{ $card['value'] }}
                                    </a>
                                @else
                                    <div class="fw-semibold mt-2">{{ $card['value'] }}</div>
                                @endif

                                @if ($card['badge'])
                                    <div class="mt-2">
                                        <x-ui.badge :tone="$card['badge']['tone']" variant="compact" :with-rail="false">
                                            {{ $card['badge']['label'] }}
                                        </x-ui.badge>
                                    </div>
                                @endif

                                <div class="small text-body-secondary mt-2">
                                    {{ $card['description'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>

            <x-ui.section-card
                title="Contexto operativo"
                subtitle="Resume el estado actual y te lleva al siguiente paso natural según el tipo de inventario."
                icon="bi-graph-up"
                class="mb-4"
            >
                <div class="row g-3">
                    @foreach ($operationalCards as $card)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $card['label'] }}</div>
                                @if ($card['href'])
                                    <a href="{{ $card['href'] }}" class="fw-semibold mt-2 d-inline-block text-decoration-none">
                                        {{ $card['value'] }}
                                    </a>
                                @else
                                    <div class="fw-semibold mt-2">{{ $card['value'] }}</div>
                                @endif

                                @if ($card['badge'])
                                    <div class="mt-2">
                                        <x-ui.badge :tone="$card['badge']['tone']" variant="compact" :with-rail="false">
                                            {{ $card['badge']['label'] }}
                                        </x-ui.badge>
                                    </div>
                                @endif

                                <div class="small text-body-secondary mt-2">
                                    {{ $card['description'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>

            @if ($productIsSerialized)
                @php
                    $retiredRow = collect($statusBreakdown)->firstWhere('status', \App\Models\Asset::STATUS_RETIRED);
                    $serializedRecords = $total + (int) ($retiredRow['count'] ?? 0);
                @endphp
                <x-ui.section-card
                    title="Distribución por estado"
                    subtitle="El total operativo excluye retirados, pero el detalle conserva el contexto histórico del producto."
                    icon="bi-pie-chart"
                    bodyClass="p-0"
                    class="mb-4"
                >
                    <x-slot:actions>
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            {{ number_format($serializedRecords) }} registros
                        </x-ui.badge>
                    </x-slot:actions>

                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                            <thead>
                                <tr>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-end">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statusBreakdown as $row)
                                    <tr @class(['gatic-table-row-retired' => $row['status'] === \App\Models\Asset::STATUS_RETIRED])>
                                        <td><x-ui.status-badge :status="$row['status']" /></td>
                                        <td class="text-end">{{ $row['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.section-card>
            @endif

            <div class="mb-3">
                <h2 class="h5 mb-1">Trazabilidad</h2>
                <p class="small text-body-secondary mb-0">
                    Timeline, notas y adjuntos permanecen en el mismo flujo; para productos por cantidad el kardex complementa esta vista con el historial de stock.
                </p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Notas {{ number_format((int) ($product->notes_count ?? 0)) }}
                    </x-ui.badge>
                    @can('attachments.view')
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Adjuntos {{ number_format((int) ($product->attachments_count ?? 0)) }}
                        </x-ui.badge>
                    @endcan
                    @if (! $productIsSerialized)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Kardex {{ number_format($headerMetrics[3]['value'] ?? 0) }}
                        </x-ui.badge>
                    @endif
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <livewire:ui.timeline-panel
                        :entity-type="\App\Models\Product::class"
                        :entity-id="$product->id"
                    />
                </div>

                <div class="col-12 col-xl-5">
                    <livewire:ui.notes-panel
                        :noteable-type="\App\Models\Product::class"
                        :noteable-id="$product->id"
                    />

                    @can('attachments.view')
                        <livewire:ui.attachments-panel
                            :attachable-type="\App\Models\Product::class"
                            :attachable-id="$product->id"
                        />
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
