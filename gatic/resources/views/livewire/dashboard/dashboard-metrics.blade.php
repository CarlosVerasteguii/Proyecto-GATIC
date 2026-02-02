<x-ui.poll method="poll" :interval-s="config('gatic.ui.polling.metrics_interval_s')">
    <div class="container">
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
        </div>
    </div>
</x-ui.poll>
