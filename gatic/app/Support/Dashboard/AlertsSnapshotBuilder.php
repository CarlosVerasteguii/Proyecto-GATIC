<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

/**
 * @phpstan-type AlertsSnapshotFilters array{location?: int, category?: int, brand?: int}
 * @phpstan-type AlertsSnapshotProductFilters array{category?: int, brand?: int}
 * @phpstan-type AlertsSnapshotInput array{
 *     canManage: bool,
 *     filters: AlertsSnapshotFilters,
 *     productFilters: AlertsSnapshotProductFilters,
 *     loansOverdueCount: int,
 *     loansDueSoonCount: int,
 *     loanDueSoonWindowDays: int,
 *     warrantiesExpiredCount: int,
 *     warrantiesDueSoonCount: int,
 *     warrantyDueSoonWindowDays: int,
 *     lowStockProductsCount: int,
 *     renewalsOverdueCount: int,
 *     renewalsDueSoonCount: int,
 *     renewalDueSoonWindowDays: int,
 *     pendingTasksActiveCount: int
 * }
 * @phpstan-type AlertsSnapshotItem array{key: string, label: string, value: int, href: string|null, variant: string}
 */
final class AlertsSnapshotBuilder
{
    /**
     * @param  AlertsSnapshotInput  $input
     * @return list<AlertsSnapshotItem>
     */
    public function build(array $input): array
    {
        $canManage = $input['canManage'];
        $filters = $input['filters'];
        $productFilters = $input['productFilters'];

        $items = [
            [
                'key' => 'loans_overdue',
                'label' => 'Préstamos vencidos',
                'value' => $input['loansOverdueCount'],
                'href' => $canManage ? route('alerts.loans.index', array_merge(['type' => 'overdue'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'loans_due_soon',
                'label' => 'Préstamos por vencer',
                'value' => $input['loansDueSoonCount'],
                'href' => $canManage
                    ? route('alerts.loans.index', array_merge(['type' => 'due-soon', 'windowDays' => $input['loanDueSoonWindowDays']], $filters))
                    : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'warranties_expired',
                'label' => 'Garantías vencidas',
                'value' => $input['warrantiesExpiredCount'],
                'href' => $canManage ? route('alerts.warranties.index', array_merge(['type' => 'expired'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'warranties_due_soon',
                'label' => 'Garantías por vencer',
                'value' => $input['warrantiesDueSoonCount'],
                'href' => $canManage
                    ? route('alerts.warranties.index', array_merge(['type' => 'due-soon', 'windowDays' => $input['warrantyDueSoonWindowDays']], $filters))
                    : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'low_stock',
                'label' => 'Stock bajo',
                'value' => $input['lowStockProductsCount'],
                'href' => $canManage ? route('alerts.stock.index', $productFilters) : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'renewals_overdue',
                'label' => 'Renovaciones vencidas',
                'value' => $input['renewalsOverdueCount'],
                'href' => $canManage ? route('alerts.renewals.index', array_merge(['type' => 'overdue'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'renewals_due_soon',
                'label' => 'Renovaciones por vencer',
                'value' => $input['renewalsDueSoonCount'],
                'href' => $canManage
                    ? route('alerts.renewals.index', array_merge(['type' => 'due-soon', 'windowDays' => $input['renewalDueSoonWindowDays']], $filters))
                    : null,
                'variant' => 'warning',
            ],
        ];

        if ($canManage) {
            $items[] = [
                'key' => 'pending_tasks',
                'label' => 'Tareas pendientes',
                'value' => $input['pendingTasksActiveCount'],
                'href' => route('pending-tasks.index'),
                'variant' => 'info',
            ];
        }

        return $items;
    }
}
