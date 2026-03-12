<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'category', 'brand', 'availability', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
        $totalRecords = $movementCount + $adjustmentCount;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header :title="'Kardex · '.($product?->name ?? 'Producto')" :subtitle="$headerSubtitle">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Productos', 'url' => route('inventory.products.index', $returnQuery)],
                        ['label' => $product->name, 'url' => route('inventory.products.show', ['product' => $product->id] + $returnQuery)],
                        ['label' => 'Kardex', 'url' => null],
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
                    <x-ui.detail-header-kpi label="Stock actual" :value="(int) ($product->qty_total ?? 0)" variant="success" />
                    <x-ui.detail-header-kpi label="Registros" :value="$totalRecords" variant="info" />
                    <x-ui.detail-header-kpi label="Movimientos" :value="$movementCount" />
                    <x-ui.detail-header-kpi label="Ajustes" :value="$adjustmentCount" variant="warning" />
                </x-slot:kpis>

                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver al producto
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
                </x-slot:actions>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Lectura rápida"
                subtitle="Confirma stock actual, volumen de actividad y nivel de ajuste antes de recorrer la cronología completa."
                icon="bi-speedometer2"
                class="mb-4"
            >
                <div class="row g-3">
                    @foreach ($summaryCards as $card)
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
                title="Cronología del inventario"
                subtitle="Cada registro conserva fecha, tipo de movimiento, cambio aplicado y saldo resultante."
                icon="bi-clock-history"
                bodyClass="p-0"
            >
                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        {{ number_format($totalRecords) }} registros
                    </x-ui.badge>
                </x-slot:actions>

                @if ($entries->total() === 0)
                    <div class="p-4">
                        <x-ui.empty-state
                            icon="bi-inbox"
                            title="Sin movimientos todavía"
                            description="Las entradas, salidas y ajustes aparecerán aquí en cuanto se registren."
                            compact
                        />
                    </div>
                @else
                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col" class="text-end">Cambio</th>
                                    <th scope="col" class="text-end">Saldo</th>
                                    <th scope="col">Actor</th>
                                    <th scope="col">Responsable</th>
                                    <th scope="col">Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    @php
                                        $delta = $entry['type'] === 'adjustment'
                                            ? $entry['qty_after'] - $entry['qty_before']
                                            : ($entry['type'] === 'out' ? -$entry['qty'] : $entry['qty']);
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $entry['date']->format('d/m/Y H:i') }}</td>
                                        <td class="text-nowrap">
                                            @switch($entry['type'])
                                                @case('out')
                                                    <x-ui.badge tone="danger" variant="compact" :with-rail="false">
                                                        {{ $entry['type_label'] }}
                                                    </x-ui.badge>
                                                    @break
                                                @case('in')
                                                    <x-ui.badge tone="success" variant="compact" :with-rail="false">
                                                        {{ $entry['type_label'] }}
                                                    </x-ui.badge>
                                                    @break
                                                @case('adjustment')
                                                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                                                        {{ $entry['type_label'] }}
                                                    </x-ui.badge>
                                                    @break
                                                @default
                                                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                                        {{ $entry['type_label'] }}
                                                    </x-ui.badge>
                                            @endswitch
                                        </td>
                                        <td class="text-end text-nowrap">
                                            @if ($delta >= 0)
                                                <span class="text-success fw-semibold">+{{ $delta }}</span>
                                            @else
                                                <span class="text-danger fw-semibold">{{ $delta }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <div class="fw-semibold">{{ $entry['qty_after'] }}</div>
                                            <div class="small text-body-secondary">Antes: {{ $entry['qty_before'] }}</div>
                                        </td>
                                        <td>{{ $entry['actor_name'] }}</td>
                                        <td>{{ $entry['employee_name'] ?? 'Sin responsable' }}</td>
                                        <td class="min-w-0">
                                            @if ($entry['note'])
                                                <span class="d-inline-block text-truncate mw-100" title="{{ $entry['note'] }}">
                                                    {{ \Illuminate\Support\Str::limit($entry['note'], 80) }}
                                                </span>
                                            @else
                                                <span class="text-body-secondary">Sin detalle</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($entries->hasPages())
                    <div class="card-footer">
                        {{ $entries->links() }}
                    </div>
                @endif
            </x-ui.section-card>
        </div>
    </div>
</div>
