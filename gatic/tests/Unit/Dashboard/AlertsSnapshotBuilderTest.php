<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\AlertsSnapshotBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertsSnapshotBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_the_expected_alert_snapshot_for_inventory_managers(): void
    {
        $snapshot = app(AlertsSnapshotBuilder::class)->build([
            'canManage' => true,
            'filters' => ['location' => 10, 'category' => 20, 'brand' => 30],
            'productFilters' => ['category' => 20, 'brand' => 30],
            'loansOverdueCount' => 4,
            'loansDueSoonCount' => 3,
            'loanDueSoonWindowDays' => 7,
            'warrantiesExpiredCount' => 2,
            'warrantiesDueSoonCount' => 5,
            'warrantyDueSoonWindowDays' => 14,
            'lowStockProductsCount' => 6,
            'renewalsOverdueCount' => 1,
            'renewalsDueSoonCount' => 8,
            'renewalDueSoonWindowDays' => 90,
            'pendingTasksActiveCount' => 9,
        ]);

        $this->assertSame([
            [
                'key' => 'loans_overdue',
                'label' => 'Préstamos vencidos',
                'value' => 4,
                'href' => route('alerts.loans.index', ['type' => 'overdue', 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'danger',
            ],
            [
                'key' => 'loans_due_soon',
                'label' => 'Préstamos por vencer',
                'value' => 3,
                'href' => route('alerts.loans.index', ['type' => 'due-soon', 'windowDays' => 7, 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'warning',
            ],
            [
                'key' => 'warranties_expired',
                'label' => 'Garantías vencidas',
                'value' => 2,
                'href' => route('alerts.warranties.index', ['type' => 'expired', 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'danger',
            ],
            [
                'key' => 'warranties_due_soon',
                'label' => 'Garantías por vencer',
                'value' => 5,
                'href' => route('alerts.warranties.index', ['type' => 'due-soon', 'windowDays' => 14, 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'warning',
            ],
            [
                'key' => 'low_stock',
                'label' => 'Stock bajo',
                'value' => 6,
                'href' => route('alerts.stock.index', ['category' => 20, 'brand' => 30]),
                'variant' => 'warning',
            ],
            [
                'key' => 'renewals_overdue',
                'label' => 'Renovaciones vencidas',
                'value' => 1,
                'href' => route('alerts.renewals.index', ['type' => 'overdue', 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'danger',
            ],
            [
                'key' => 'renewals_due_soon',
                'label' => 'Renovaciones por vencer',
                'value' => 8,
                'href' => route('alerts.renewals.index', ['type' => 'due-soon', 'windowDays' => 90, 'location' => 10, 'category' => 20, 'brand' => 30]),
                'variant' => 'warning',
            ],
            [
                'key' => 'pending_tasks',
                'label' => 'Tareas pendientes',
                'value' => 9,
                'href' => route('pending-tasks.index'),
                'variant' => 'info',
            ],
        ], $snapshot);
    }

    #[Test]
    public function it_keeps_the_same_snapshot_keys_and_labels_without_links_for_non_managers(): void
    {
        $snapshot = app(AlertsSnapshotBuilder::class)->build([
            'canManage' => false,
            'filters' => ['location' => 10, 'category' => 20, 'brand' => 30],
            'productFilters' => ['category' => 20, 'brand' => 30],
            'loansOverdueCount' => 4,
            'loansDueSoonCount' => 3,
            'loanDueSoonWindowDays' => 7,
            'warrantiesExpiredCount' => 2,
            'warrantiesDueSoonCount' => 5,
            'warrantyDueSoonWindowDays' => 14,
            'lowStockProductsCount' => 6,
            'renewalsOverdueCount' => 1,
            'renewalsDueSoonCount' => 8,
            'renewalDueSoonWindowDays' => 90,
            'pendingTasksActiveCount' => 9,
        ]);

        $this->assertSame([
            'loans_overdue',
            'loans_due_soon',
            'warranties_expired',
            'warranties_due_soon',
            'low_stock',
            'renewals_overdue',
            'renewals_due_soon',
        ], array_column($snapshot, 'key'));

        $this->assertSame([
            'Préstamos vencidos',
            'Préstamos por vencer',
            'Garantías vencidas',
            'Garantías por vencer',
            'Stock bajo',
            'Renovaciones vencidas',
            'Renovaciones por vencer',
        ], array_column($snapshot, 'label'));

        $this->assertSame([4, 3, 2, 5, 6, 1, 8], array_column($snapshot, 'value'));
        $this->assertSame(['danger', 'warning', 'danger', 'warning', 'warning', 'danger', 'warning'], array_column($snapshot, 'variant'));
        $this->assertSame([null, null, null, null, null, null, null], array_column($snapshot, 'href'));
    }
}
