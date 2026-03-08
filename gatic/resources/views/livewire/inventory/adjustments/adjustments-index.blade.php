<div class="container position-relative">
    @php
        $resultsCount = $adjustments->total();
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Historial de ajustes"
                subtitle="Consulta correcciones manuales de inventario y valida quién aplicó cada ajuste del baseline."
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Ajustes', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Ajustes <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>
                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                        Solo Admin
                    </x-ui.badge>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.index') }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver a inventario
                    </a>
                </x-slot:actions>

                <div class="d-flex flex-column gap-3 mb-3">
                    <div class="small text-body-secondary">
                        Cada registro conserva actor, motivo y cantidad de entradas afectadas. Úsalo como referencia rápida antes de revisar auditoría o detalle de producto/activo.
                    </div>
                </div>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} ajuste{{ $resultsCount === 1 ? '' : 's' }} registrados.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                        <thead>
                            <tr>
                                <th>Ajuste</th>
                                <th>Actor</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                                <th class="text-end">Entradas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $adjustment)
                                <tr wire:key="inventory-adjustment-{{ $adjustment->id }}">
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">Ajuste #{{ $adjustment->id }}</div>
                                        <div class="small text-body-secondary">Corrección manual de inventario</div>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="text-truncate">{{ $adjustment->user?->name ?? 'Usuario no disponible' }}</div>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="text-break">{{ $adjustment->reason }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">{{ $adjustment->created_at?->format('d/m/Y') ?? '—' }}</div>
                                        <div class="small text-body-secondary">{{ $adjustment->created_at?->format('H:i') ?? '—' }}</div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                            {{ (int) ($adjustment->entries_count ?? 0) }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-ui.empty-state
                                            icon="bi-sliders"
                                            title="No hay ajustes registrados"
                                            description="Cuando un administrador corrija cantidades, estado o ubicación, el historial aparecerá aquí."
                                            compact
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $adjustments->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
