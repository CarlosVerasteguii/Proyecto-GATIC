<x-ui.poll method="poll" :interval-s="config('gatic.ui.polling.metrics_interval_s')">
    <div class="container position-relative dash-page" data-page="dashboard">
        <x-ui.long-request target="poll,refreshNow" />

        @if (is_string($errorId) && $errorId !== '')
            <div class="mb-3">
                <x-ui.error-alert-with-id
                    message="Ocurrió un error al actualizar las métricas."
                    :error-id="$errorId"
                />
            </div>
        @endif

        <div class="dash-header">
            <div>
                <h1 class="h3 mb-1 dash-title">Dashboard</h1>
                <div class="text-muted small">Estado del inventario y alertas operativas.</div>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    wire:click="refreshNow"
                    wire:loading.attr="disabled"
                    wire:target="refreshNow"
                >
                    <span wire:loading.remove wire:target="refreshNow">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Actualizar
                    </span>
                    <span wire:loading wire:target="refreshNow">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Actualizando...
                    </span>
                </button>
            </div>
        </div>

        @php
            $canManageInventory = \Illuminate\Support\Facades\Gate::allows('inventory.manage');
            $canViewInventory = \Illuminate\Support\Facades\Gate::allows('inventory.view');

            $hrefLoansOverdue = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.loans.index')
                ? route('alerts.loans.index', ['type' => 'overdue'])
                : null;

            $hrefLoansDueSoon = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.loans.index')
                ? route('alerts.loans.index', ['type' => 'due-soon', 'windowDays' => $loanDueSoonWindowDays])
                : null;

            $hrefWarrantiesExpired = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.warranties.index')
                ? route('alerts.warranties.index', ['type' => 'expired'])
                : null;

            $hrefWarrantiesDueSoon = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.warranties.index')
                ? route('alerts.warranties.index', ['type' => 'due-soon', 'windowDays' => $warrantyDueSoonWindowDays])
                : null;

            $hrefLowStock = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.stock.index')
                ? route('alerts.stock.index')
                : null;

            $hrefRenewalsOverdue = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.renewals.index')
                ? route('alerts.renewals.index', ['type' => 'overdue'])
                : null;

            $hrefRenewalsDueSoon = $canManageInventory && \Illuminate\Support\Facades\Route::has('alerts.renewals.index')
                ? route('alerts.renewals.index', ['type' => 'due-soon', 'windowDays' => $renewalDueSoonWindowDays])
                : null;

            $hrefPendingTasks = $canManageInventory && \Illuminate\Support\Facades\Route::has('pending-tasks.index')
                ? route('pending-tasks.index')
                : null;

            $hrefAssetsLoaned = $canViewInventory && \Illuminate\Support\Facades\Route::has('inventory.assets.index')
                ? route('inventory.assets.index', ['status' => \App\Models\Asset::STATUS_LOANED])
                : null;

            $hrefAssetsPendingRetirement = $canViewInventory && \Illuminate\Support\Facades\Route::has('inventory.assets.index')
                ? route('inventory.assets.index', ['status' => \App\Models\Asset::STATUS_PENDING_RETIREMENT])
                : null;

            $hrefAssetsAssigned = $canViewInventory && \Illuminate\Support\Facades\Route::has('inventory.assets.index')
                ? route('inventory.assets.index', ['status' => \App\Models\Asset::STATUS_ASSIGNED])
                : null;

            $hrefAssetsUnavailable = $canViewInventory && \Illuminate\Support\Facades\Route::has('inventory.assets.index')
                ? route('inventory.assets.index', ['status' => 'unavailable'])
                : null;

            $hrefAssetsAvailable = $canViewInventory && \Illuminate\Support\Facades\Route::has('inventory.assets.index')
                ? route('inventory.assets.index', ['status' => \App\Models\Asset::STATUS_AVAILABLE])
                : null;

            $movementsDeltaPct = $movementsYesterday > 0 ? (($movementsToday - $movementsYesterday) / $movementsYesterday) * 100 : null;
            $movementsDeltaVariant = is_float($movementsDeltaPct) && $movementsDeltaPct < 0 ? 'success' : 'warning';
            $movementsDeltaLabel = is_float($movementsDeltaPct) ? sprintf('%+.1f%% vs ayer', $movementsDeltaPct) : null;

            $assetsOperativeTotal = $assetsAvailable + $assetsUnavailable;
            $fmtShareLabel = static function (int $part) use ($assetsOperativeTotal): ?string {
                if ($assetsOperativeTotal <= 0) {
                    return null;
                }

                $pct = ($part / $assetsOperativeTotal) * 100;

                return sprintf('%d%% del total', (int) round($pct));
            };

            $shareUnavailable = $fmtShareLabel($assetsUnavailable);
            $shareAvailable = $fmtShareLabel($assetsAvailable);
            $shareLoaned = $fmtShareLabel($assetsLoaned);
            $shareAssigned = $fmtShareLabel($assetsAssigned);
            $sharePendingRetirement = $fmtShareLabel($assetsPendingRetirement);
        @endphp

        {{-- Alertas (jerarquia por urgencia) --}}
        <div class="dash-group mb-4" aria-label="Alertas">
            <div class="dash-group-header">
                <div>
                    <div class="dash-group-title">
                        <i class="bi bi-bell me-1" aria-hidden="true"></i>
                        Alertas
                    </div>
                    <div class="dash-group-hint">Primero lo crítico, luego lo próximo a vencer.</div>
                </div>
                <div class="dash-group-note small text-muted">
                    Deltas: vs ayer. Drill-down solo para gestión.
                </div>
            </div>

            <div class="dash-subgroup">
                <div class="dash-subgroup-title">Críticas</div>
                <div class="dash-grid-4" aria-label="Alertas críticas">
                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Vencidos"
                        :value="$loansOverdueCount"
                        testid="dashboard-metric-loans-overdue"
                        description="Préstamos con vencimiento en el pasado"
                        variant="danger"
                        icon="bi-clock-history"
                        :href="$hrefLoansOverdue"
                    >
                        @if (is_string($loansOverdueDeltaLabel) && $loansOverdueDeltaLabel !== '' && is_string($loansOverdueDeltaVariant) && $loansOverdueDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $loansOverdueDeltaVariant }}">{{ $loansOverdueDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Garantías Vencidas"
                        :value="$warrantiesExpiredCount"
                        testid="dashboard-metric-warranties-expired"
                        description="Activos con garantía expirada (excluye retirados)"
                        variant="danger"
                        icon="bi-shield-x"
                        :href="$hrefWarrantiesExpired"
                        ctaTestid="dashboard-warranty-expired-link"
                    >
                        @if (is_string($warrantiesExpiredDeltaLabel) && $warrantiesExpiredDeltaLabel !== '' && is_string($warrantiesExpiredDeltaVariant) && $warrantiesExpiredDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $warrantiesExpiredDeltaVariant }}">{{ $warrantiesExpiredDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Renovaciones Vencidas"
                        :value="$renewalsOverdueCount"
                        description="Activos con reemplazo esperado en el pasado"
                        variant="danger"
                        icon="bi-arrow-repeat"
                        :href="$hrefRenewalsOverdue"
                    >
                        @if (is_string($renewalsOverdueDeltaLabel) && $renewalsOverdueDeltaLabel !== '' && is_string($renewalsOverdueDeltaVariant) && $renewalsOverdueDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $renewalsOverdueDeltaVariant }}">{{ $renewalsOverdueDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Stock Bajo"
                        :value="$lowStockProductsCount"
                        testid="dashboard-metric-products-low-stock"
                        description="Productos por cantidad bajo umbral configurado"
                        variant="warning"
                        icon="bi-box-seam"
                        :href="$hrefLowStock"
                    />
                </div>
            </div>

            <div class="dash-subgroup mt-3">
                <div class="dash-subgroup-title">Próximas</div>
                <div class="dash-grid-3" aria-label="Alertas próximas">
                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Por vencer"
                        :value="$loansDueSoonCount"
                        testid="dashboard-metric-loans-due-soon"
                        description="Vencen hoy o en los próximos {{ $loanDueSoonWindowDays }} días"
                        variant="warning"
                        icon="bi-hourglass-split"
                        :href="$hrefLoansDueSoon"
                    >
                        @if (is_string($loansDueSoonDeltaLabel) && $loansDueSoonDeltaLabel !== '' && is_string($loansDueSoonDeltaVariant) && $loansDueSoonDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $loansDueSoonDeltaVariant }}">{{ $loansDueSoonDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Garantías Por Vencer"
                        :value="$warrantiesDueSoonCount"
                        testid="dashboard-metric-warranties-due-soon"
                        description="Vencen en los próximos {{ $warrantyDueSoonWindowDays }} días"
                        variant="warning"
                        icon="bi-shield-exclamation"
                        :href="$hrefWarrantiesDueSoon"
                        ctaTestid="dashboard-warranty-due-soon-link"
                    >
                        @if (is_string($warrantiesDueSoonDeltaLabel) && $warrantiesDueSoonDeltaLabel !== '' && is_string($warrantiesDueSoonDeltaVariant) && $warrantiesDueSoonDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $warrantiesDueSoonDeltaVariant }}">{{ $warrantiesDueSoonDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        class="dash-kpi--compact"
                        label="Renovaciones Por Vencer"
                        :value="$renewalsDueSoonCount"
                        description="Vencen en los próximos {{ $renewalDueSoonWindowDays }} días"
                        variant="warning"
                        icon="bi-calendar2-week"
                        :href="$hrefRenewalsDueSoon"
                    >
                        @if (is_string($renewalsDueSoonDeltaLabel) && $renewalsDueSoonDeltaLabel !== '' && is_string($renewalsDueSoonDeltaVariant) && $renewalsDueSoonDeltaVariant !== '')
                            <div class="mt-2">
                                <span class="badge text-bg-{{ $renewalsDueSoonDeltaVariant }}">{{ $renewalsDueSoonDeltaLabel }}</span>
                            </div>
                        @endif
                    </x-ui.kpi-card>
                </div>
            </div>
        </div>

        {{-- Estado de activos --}}
        <div class="dash-group mb-4" aria-label="Estado de activos">
            <div class="dash-group-header">
                <div>
                    <div class="dash-group-title">
                        <i class="bi bi-hdd me-1" aria-hidden="true"></i>
                        Estado de activos
                    </div>
                    <div class="dash-group-hint">Disponibilidad operativa y distribución por estatus.</div>
                </div>
            </div>

            <div class="dash-grid-4" aria-label="KPIs principales">
                <x-ui.kpi-card
                    label="Activos No Disponibles"
                    :value="$assetsUnavailable"
                    testid="dashboard-metric-assets-unavailable"
                    description="Asignados + Prestados + Pendientes de Retiro"
                    variant="secondary"
                    icon="bi-slash-circle"
                    :href="$hrefAssetsUnavailable"
                    cta="Ver activos"
                >
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="dash-chip">Prestados <strong>{{ $assetsLoaned }}</strong></span>
                        <span class="dash-chip">Asignados <strong>{{ $assetsAssigned }}</strong></span>
                        <span class="dash-chip">Retiro <strong>{{ $assetsPendingRetirement }}</strong></span>
                    </div>
                    @if (is_string($shareUnavailable) && $shareUnavailable !== '')
                        <div class="mt-2">
                            <span class="dash-chip">{{ $shareUnavailable }}</span>
                        </div>
                    @endif
                </x-ui.kpi-card>

                <x-ui.kpi-card
                    label="Activos Prestados"
                    :value="$assetsLoaned"
                    testid="dashboard-metric-assets-loaned"
                    description='Activos en estado "Prestado"'
                    variant="warning"
                    icon="bi-box-arrow-right"
                    :href="$hrefAssetsLoaned"
                    cta="Ver activos"
                >
                    @if (is_string($shareLoaned) && $shareLoaned !== '')
                        <div class="mt-2">
                            <span class="dash-chip">{{ $shareLoaned }}</span>
                        </div>
                    @endif
                </x-ui.kpi-card>

                <x-ui.kpi-card
                    label="Activos Asignados"
                    :value="$assetsAssigned"
                    testid="dashboard-metric-assets-assigned"
                    description="Activos asignados a empleados"
                    variant="primary"
                    icon="bi-person-check"
                    :href="$hrefAssetsAssigned"
                    cta="Ver activos"
                >
                    @if (is_string($shareAssigned) && $shareAssigned !== '')
                        <div class="mt-2">
                            <span class="dash-chip">{{ $shareAssigned }}</span>
                        </div>
                    @endif
                </x-ui.kpi-card>

                <x-ui.kpi-card
                    label="Pendientes de Retiro"
                    :value="$assetsPendingRetirement"
                    testid="dashboard-metric-assets-pending-retirement"
                    description="Activos marcados para dar de baja"
                    variant="danger"
                    icon="bi-trash"
                    :href="$hrefAssetsPendingRetirement"
                    cta="Ver activos"
                >
                    @if (is_string($sharePendingRetirement) && $sharePendingRetirement !== '')
                        <div class="mt-2">
                            <span class="dash-chip">{{ $sharePendingRetirement }}</span>
                        </div>
                    @endif
                </x-ui.kpi-card>
            </div>
        </div>

        {{-- Operación --}}
        <div class="dash-group mb-4" aria-label="Operación">
            <div class="dash-group-header">
                <div>
                    <div class="dash-group-title">
                        <i class="bi bi-activity me-1" aria-hidden="true"></i>
                        Operación
                    </div>
                    <div class="dash-group-hint">Ritmo de trabajo y backlog.</div>
                </div>
            </div>

            <div class="{{ $canManageInventory ? 'dash-grid-4' : 'dash-grid-2' }}">
                <x-ui.kpi-card
                    label="Activos Disponibles"
                    :value="$assetsAvailable"
                    description='Activos en estado "Disponible"'
                    variant="success"
                    icon="bi-check2-circle"
                    :href="$hrefAssetsAvailable"
                    cta="Ver activos"
                >
                    @if (is_string($shareAvailable) && $shareAvailable !== '')
                        <div class="mt-2">
                            <span class="dash-chip">{{ $shareAvailable }}</span>
                        </div>
                    @endif
                </x-ui.kpi-card>

                <x-ui.kpi-card
                    label="Movimientos Hoy"
                    :value="$movementsToday"
                    testid="dashboard-metric-movements-today"
                    description="Activos + Productos por cantidad"
                    variant="info"
                    icon="bi-activity"
                >
                    @if (is_string($movementsDeltaLabel) && $movementsDeltaLabel !== '')
                        <div class="mt-2">
                            <span class="badge text-bg-{{ $movementsDeltaVariant }}">{{ $movementsDeltaLabel }}</span>
                        </div>
                    @endif

                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="dash-chip">Ayer <strong>{{ $movementsYesterday }}</strong></span>
                        <span class="dash-chip">Rango <strong>{{ (int) $trendRangeDays }}d</strong></span>
                    </div>
                </x-ui.kpi-card>

                @can('inventory.manage')
                    <x-ui.kpi-card
                        label="Tareas pendientes"
                        :value="$pendingTasksActiveCount"
                        description="Backlog activo"
                        variant="info"
                        icon="bi-list-check"
                        :href="$hrefPendingTasks"
                        cta="Ver tareas"
                    >
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="dash-chip">Listas <strong>{{ $pendingTasksReadyCount }}</strong></span>
                            <span class="dash-chip">Procesando <strong>{{ $pendingTasksProcessingCount }}</strong></span>
                        </div>
                    </x-ui.kpi-card>

                    <x-ui.kpi-card
                        label="Valor del Inventario"
                        :value="number_format((float) $totalInventoryValue, 2) . ' ' . $defaultCurrency"
                        testid="dashboard-metric-total-inventory-value"
                        description="Excluye retirados"
                        variant="success"
                        icon="bi-cash-coin"
                    >
                        <div class="mt-2">
                            <span class="dash-chip">{{ $defaultCurrency }} · solo moneda default</span>
                        </div>
                    </x-ui.kpi-card>
                @endcan
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-4">
            <div class="col-lg-8">
                <x-ui.section-card title="Tendencia: Movimientos" subtitle="Últimos {{ (int) $trendRangeDays }} días" icon="bi-graph-up">
                    <x-slot:actions>
                        <select class="form-select form-select-sm" wire:model.live="trendRangeDays" aria-label="Rango de tendencia">
                            <option value="7">7 días</option>
                            <option value="30">30 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </x-slot:actions>

                    <div wire:ignore class="dash-chart">
                        <canvas id="dashboardMovementsTrend" aria-label="Tendencia de movimientos"></canvas>
                    </div>
                </x-ui.section-card>
            </div>

            <div class="col-lg-4">
                <x-ui.section-card title="Alertas (conteo actual)" subtitle="Click para drill-down" icon="bi-bell">
                    <div wire:ignore class="dash-chart">
                        <canvas id="dashboardAlertsSnapshot" aria-label="Alertas actuales"></canvas>
                    </div>
                    <div class="mt-3 small text-muted">
                        Los enlaces a detalle aparecen solo para usuarios con permisos de gestión.
                    </div>
                </x-ui.section-card>
            </div>
        </div>

        @can('inventory.manage')
            {{-- Distribución del valor (solo moneda default) --}}
            @if (count($valueByCategory) > 0 || count($valueByBrand) > 0)
                <div class="row g-4 mt-4">
                    {{-- Valor por Categoría --}}
                    @if (count($valueByCategory) > 0)
                        <div class="col-lg-6">
                            <x-ui.section-card
                                title="Valor por Categoría"
                                subtitle="Top {{ (int) $valueBreakdownTopN }}"
                                icon="bi-pie-chart"
                                body-class="p-0"
                            >
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
                                                            {{ $item['name'] }} <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
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
                            </x-ui.section-card>
                        </div>
                    @endif

                    {{-- Valor por Marca --}}
                    @if (count($valueByBrand) > 0)
                        <div class="col-lg-6">
                            <x-ui.section-card
                                title="Valor por Marca"
                                subtitle="Top {{ (int) $valueBreakdownTopN }}"
                                icon="bi-tags"
                                body-class="p-0"
                            >
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
                                                            {{ $item['name'] }} <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
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
                            </x-ui.section-card>
                        </div>
                    @endif
                </div>
            @endif
        @endcan

        {{-- Actividad Reciente --}}
        @if (count($recentActivity) > 0)
            <div class="mt-4">
                <x-ui.section-card
                    title="Actividad Reciente"
                    subtitle="Últimos {{ count($recentActivity) }} eventos"
                    icon="bi-activity"
                    body-class="p-0"
                >
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
                                        <i class="{{ $event['icon'] }}" aria-hidden="true"></i>
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
                </x-ui.section-card>
            </div>
        @endif
    </div>
</x-ui.poll>
