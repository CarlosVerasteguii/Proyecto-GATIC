<x-ui.poll method="poll" :interval-s="config('gatic.ui.polling.metrics_interval_s')">
    <div class="container position-relative">
        <x-ui.long-request target="poll,refreshNow" />

        @if (is_string($errorId) && $errorId !== '')
            <div class="mb-3">
                <x-ui.error-alert-with-id
                    message="Ocurrió un error al actualizar las métricas."
                    :error-id="$errorId"
                />
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Dashboard</h1>
            <div class="d-flex align-items-center gap-3">
                <x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    wire:click="refreshNow"
                    wire:loading.attr="disabled"
                    wire:target="refreshNow"
                >
                    <span wire:loading.remove wire:target="refreshNow">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                    </span>
                    <span wire:loading wire:target="refreshNow">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Actualizando...
                    </span>
                </button>
            </div>
        </div>

        <div class="row g-4">
            {{-- Activos Prestados --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-warning" data-testid="dashboard-metric-assets-loaned">{{ $assetsLoaned }}</h2>
                        <h6 class="card-title text-muted mb-2">Activos Prestados</h6>
                        <small class="text-muted">
                            Activos en estado "Prestado"
                        </small>
                    </div>
                </div>
            </div>

            {{-- Préstamos Vencidos --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-danger">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-danger" data-testid="dashboard-metric-loans-overdue">{{ $loansOverdueCount }}</h2>
                        <h6 class="card-title text-muted mb-2">Vencidos</h6>
                        <small class="text-muted">
                            Activos prestados con vencimiento en el pasado
                        </small>

                        @can('inventory.manage')
                            @if (\Illuminate\Support\Facades\Route::has('alerts.loans.index'))
                                <div class="mt-2">
                                    <a
                                        href="{{ route('alerts.loans.index', ['type' => 'overdue']) }}"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Ver lista
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Préstamos Por vencer --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-warning" data-testid="dashboard-metric-loans-due-soon">{{ $loansDueSoonCount }}</h2>
                        <h6 class="card-title text-muted mb-2">Por vencer</h6>
                        <small class="text-muted">
                            Vencen hoy o en los próximos {{ $loanDueSoonWindowDays }} días
                        </small>

                        @can('inventory.manage')
                            @if (\Illuminate\Support\Facades\Route::has('alerts.loans.index'))
                                <div class="mt-2">
                                    <a
                                        href="{{ route('alerts.loans.index', ['type' => 'due-soon', 'windowDays' => $loanDueSoonWindowDays]) }}"
                                        class="btn btn-sm btn-outline-warning"
                                    >
                                        Ver lista
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Garantías Vencidas --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-danger">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-danger" data-testid="dashboard-metric-warranties-expired">{{ $warrantiesExpiredCount }}</h2>
                        <h6 class="card-title text-muted mb-2">Garantías Vencidas</h6>
                        <small class="text-muted">
                            Activos con garantía expirada (excluye retirados)
                        </small>

                        @can('inventory.manage')
                            @if (\Illuminate\Support\Facades\Route::has('alerts.warranties.index'))
                                <div class="mt-2">
                                    <a
                                        href="{{ route('alerts.warranties.index', ['type' => 'expired']) }}"
                                        class="btn btn-sm btn-outline-danger"
                                        data-testid="dashboard-warranty-expired-link"
                                    >
                                        Ver lista
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Garantías Por Vencer --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-warning" data-testid="dashboard-metric-warranties-due-soon">{{ $warrantiesDueSoonCount }}</h2>
                        <h6 class="card-title text-muted mb-2">Garantías Por Vencer</h6>
                        <small class="text-muted">
                            Vencen en los próximos {{ $warrantyDueSoonWindowDays }} días
                        </small>

                        @can('inventory.manage')
                            @if (\Illuminate\Support\Facades\Route::has('alerts.warranties.index'))
                                <div class="mt-2">
                                    <a
                                        href="{{ route('alerts.warranties.index', ['type' => 'due-soon', 'windowDays' => $warrantyDueSoonWindowDays]) }}"
                                        class="btn btn-sm btn-outline-warning"
                                        data-testid="dashboard-warranty-due-soon-link"
                                    >
                                        Ver lista
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Activos Pendientes de Retiro --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-danger">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-danger" data-testid="dashboard-metric-assets-pending-retirement">{{ $assetsPendingRetirement }}</h2>
                        <h6 class="card-title text-muted mb-2">Pendientes de Retiro</h6>
                        <small class="text-muted">
                            Activos marcados para dar de baja
                        </small>
                    </div>
                </div>
            </div>

            {{-- Activos Asignados --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-primary">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-primary" data-testid="dashboard-metric-assets-assigned">{{ $assetsAssigned }}</h2>
                        <h6 class="card-title text-muted mb-2">Activos Asignados</h6>
                        <small class="text-muted">
                            Activos asignados a empleados
                        </small>
                    </div>
                </div>
            </div>

            {{-- Activos No Disponibles --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-secondary">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-secondary" data-testid="dashboard-metric-assets-unavailable">{{ $assetsUnavailable }}</h2>
                        <h6 class="card-title text-muted mb-2">Activos No Disponibles</h6>
                        <small class="text-muted">
                            Asignados + Prestados + Pendientes de Retiro
                        </small>
                    </div>
                </div>
            </div>

            {{-- Movimientos Hoy --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-info">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-info" data-testid="dashboard-metric-movements-today">{{ $movementsToday }}</h2>
                        <h6 class="card-title text-muted mb-2">Movimientos Hoy</h6>
                        <small class="text-muted">
                            Activos + Productos por cantidad
                        </small>
                    </div>
                </div>
            </div>

            {{-- Productos con Stock Bajo --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-warning" data-testid="dashboard-metric-products-low-stock">{{ $lowStockProductsCount }}</h2>
                        <h6 class="card-title text-muted mb-2">Stock Bajo</h6>
                        <small class="text-muted">
                            Productos por cantidad bajo umbral configurado
                        </small>

                        @can('inventory.manage')
                            @if (\Illuminate\Support\Facades\Route::has('alerts.stock.index'))
                                <div class="mt-2">
                                    <a
                                        href="{{ route('alerts.stock.index') }}"
                                        class="btn btn-sm btn-outline-warning"
                                    >
                                        Ver lista
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @can('inventory.manage')
                {{-- Valor Total del Inventario (en moneda default, sin conversión) --}}
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <h2 class="display-6 fw-bold text-success" data-testid="dashboard-metric-total-inventory-value">
                                {{ number_format((float) $totalInventoryValue, 2) }} {{ $defaultCurrency }}
                            </h2>
                            <h6 class="card-title text-muted mb-2">Valor del Inventario</h6>
                            <small class="text-muted">
                                Pesos Mexicanos ({{ $defaultCurrency }})
                            </small>
                            <div class="mt-1">
                                <small class="text-muted fst-italic">Excluye activos retirados</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        @can('inventory.manage')
            {{-- Distribución del valor (solo moneda default) --}}
            @if (count($valueByCategory) > 0 || count($valueByBrand) > 0)
                <div class="row g-4 mt-2">
                    {{-- Valor por Categoría --}}
                    @if (count($valueByCategory) > 0)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="bi bi-pie-chart me-1"></i>
                                        Valor por Categoría
                                    </div>
                                    <small class="text-muted">Top {{ (int) $valueBreakdownTopN }}</small>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" data-testid="dashboard-value-by-category">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Categoría</th>
                                                <th class="text-end">Valor ({{ $defaultCurrency }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($valueByCategory as $item)
                                                <tr @class(['table-light' => $item['name'] === 'Otros'])>
                                                    <td>
                                                        @if ($item['id'] !== null)
                                                            <a href="{{ route('inventory.products.index', ['category' => $item['id']]) }}" class="text-decoration-none">
                                                                {{ $item['name'] }} <i class="bi bi-box-arrow-up-right small"></i>
                                                            </a>
                                                        @else
                                                            {{ $item['name'] }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $item['value'], 2) }} {{ $defaultCurrency }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Valor por Marca --}}
                    @if (count($valueByBrand) > 0)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="bi bi-tags me-1"></i>
                                        Valor por Marca
                                    </div>
                                    <small class="text-muted">Top {{ (int) $valueBreakdownTopN }}</small>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" data-testid="dashboard-value-by-brand">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Marca</th>
                                                <th class="text-end">Valor ({{ $defaultCurrency }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($valueByBrand as $item)
                                                <tr @class(['table-light' => $item['name'] === 'Otros' || $item['name'] === 'Sin marca'])>
                                                    <td>
                                                        @if ($item['id'] !== null)
                                                            <a href="{{ route('inventory.products.index', ['brand' => $item['id']]) }}" class="text-decoration-none">
                                                                {{ $item['name'] }} <i class="bi bi-box-arrow-up-right small"></i>
                                                            </a>
                                                        @else
                                                            {{ $item['name'] }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $item['value'], 2) }} {{ $defaultCurrency }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endcan

        {{-- Actividad Reciente --}}
        @if (count($recentActivity) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-activity me-1"></i>
                                Actividad Reciente
                            </div>
                            <small class="text-muted">Últimos {{ count($recentActivity) }} eventos</small>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0" data-testid="dashboard-recent-activity">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 2rem;"></th>
                                        <th>Evento</th>
                                        <th>Detalle</th>
                                        <th>Usuario</th>
                                        <th class="text-end">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentActivity as $event)
                                        <tr>
                                            <td class="text-center text-muted">
                                                <i class="{{ $event['icon'] }}"></i>
                                            </td>
                                            <td>
                                                @if ($event['route'] !== null)
                                                    <a href="{{ $event['route'] }}" class="text-decoration-none">
                                                        {{ $event['title'] }}
                                                    </a>
                                                @else
                                                    {{ $event['title'] }}
                                                @endif
                                                <br>
                                                <small class="text-muted">{{ $event['label'] }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($event['summary'], 80) }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $event['actor'] ?? '—' }}</small>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <small class="text-muted">
                                                    {{ $event['occurred_at_human'] ?? \Illuminate\Support\Carbon::parse($event['occurred_at'])->diffForHumans() }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-ui.poll>
