<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
                    Volver al producto
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    Kardex: {{ $product?->name ?? 'Producto' }}
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-0">
                        Historial cronológico de movimientos y ajustes de inventario para este producto por cantidad.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Movimientos</span>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">{{ $entries->total() }} registros</x-ui.badge>
                </div>
                <div class="card-body p-0">
                    @if ($entries->total() === 0)
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted" aria-hidden="true"></i>
                            <p class="text-muted mt-2 mb-0">Sin movimientos aún</p>
                            <p class="text-muted small">Los movimientos y ajustes aparecerán aquí cuando se registren.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-gatic-head">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th class="text-end">Cantidad</th>
                                        <th>Actor</th>
                                        <th>Empleado</th>
                                        <th>Nota/Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entries as $entry)
                                        <tr>
                                            <td class="text-nowrap">
                                                {{ $entry['date']->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                @switch($entry['type'])
                                                    @case('out')
                                                        <x-ui.badge tone="danger" variant="compact" :with-rail="false">{{ $entry['type_label'] }}</x-ui.badge>
                                                        @break
                                                    @case('in')
                                                        <x-ui.badge tone="success" variant="compact" :with-rail="false">{{ $entry['type_label'] }}</x-ui.badge>
                                                        @break
                                                    @case('adjustment')
                                                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">{{ $entry['type_label'] }}</x-ui.badge>
                                                        @break
                                                    @default
                                                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">{{ $entry['type_label'] }}</x-ui.badge>
                                                @endswitch
                                            </td>
                                            <td class="text-end">
                                                @if ($entry['type'] === 'adjustment')
                                                    @php
                                                        $delta = $entry['qty_after'] - $entry['qty_before'];
                                                    @endphp
                                                    @if ($delta >= 0)
                                                        <span class="text-success">+{{ $delta }}</span>
                                                    @else
                                                        <span class="text-danger">{{ $delta }}</span>
                                                    @endif
                                                    <small class="text-muted d-block">
                                                        {{ $entry['qty_before'] }} → {{ $entry['qty_after'] }}
                                                    </small>
                                                @else
                                                    @if ($entry['type'] === 'out')
                                                        <span class="text-danger">-{{ $entry['qty'] }}</span>
                                                    @else
                                                        <span class="text-success">+{{ $entry['qty'] }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $entry['actor_name'] }}</td>
                                            <td>{{ $entry['employee_name'] ?? '-' }}</td>
                                            <td>
                                                @if ($entry['note'])
                                                    <span title="{{ $entry['note'] }}">
                                                        {{ \Illuminate\Support\Str::limit($entry['note'], 40) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if ($entries->hasPages())
                    <div class="card-footer">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
