<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Alertas', 'url' => null],
                        ]" />
                        <span class="fw-medium">Alertas de préstamos</span>
                    </div>
                </div>

                <div class="card-body">
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a
                                class="nav-link {{ $type === 'overdue' ? 'active' : '' }}"
                                href="{{ route('alerts.loans.index', ['type' => 'overdue']) }}"
                            >
                                Vencidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link {{ $type === 'due-soon' ? 'active' : '' }}"
                                href="{{ route('alerts.loans.index', ['type' => 'due-soon', 'windowDays' => $resolvedWindowDays]) }}"
                            >
                                Por vencer
                            </a>
                        </li>
                    </ul>

                    @if ($type === 'due-soon')
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-12 col-md-4">
                                <label for="loan-alerts-window" class="form-label">Ventana (días)</label>
                                <select
                                    id="loan-alerts-window"
                                    class="form-select"
                                    wire:model.live="windowDays"
                                >
                                    @foreach ($windowDaysOptions as $days)
                                        <option value="{{ $days }}">{{ $days }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-8">
                                <small class="text-muted">
                                    Incluye los préstamos que vencen hoy hasta {{ $resolvedWindowDays }} días.
                                </small>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Activo</th>
                                    <th>Empleado</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end">{{ $type === 'overdue' ? 'Días vencidos' : 'Días restantes' }}</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($alerts as $asset)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">
                                                {{ $asset->product?->name ?? '—' }}
                                            </div>
                                            <div class="small text-muted">
                                                <span class="me-2">Serial: {{ $asset->serial }}</span>
                                                <span>Asset tag: {{ $asset->asset_tag ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($asset->currentEmployee)
                                                <div class="fw-medium">{{ $asset->currentEmployee->rpe }}</div>
                                                <div class="small text-muted">{{ $asset->currentEmployee->name }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($asset->loan_due_date)
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    {{ $asset->loan_due_date->format('d/m/Y') }}
                                                </small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($asset->loan_due_date)
                                                @if ($type === 'overdue')
                                                    {{ $asset->loan_due_date->diffInDays($today) }}
                                                @else
                                                    {{ $today->diffInDays($asset->loan_due_date) }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Ver detalle
                                            </a>

                                            @can('inventory.manage')
                                                @if ($asset->status === \App\Models\Asset::STATUS_LOANED)
                                                    <a
                                                        href="{{ route('inventory.products.assets.return', ['product' => $asset->product_id, 'asset' => $asset->id, 'returnTo' => $returnTo]) }}"
                                                        class="btn btn-sm btn-outline-primary"
                                                    >
                                                        Devolver
                                                    </a>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No hay alertas para mostrar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $alerts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
