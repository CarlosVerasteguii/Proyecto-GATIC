<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\CriticalQueuePreviewBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CriticalQueuePreviewBuilderTest extends TestCase
{
    #[Test]
    public function it_orders_items_by_priority_then_score_and_removes_internal_ranking_fields(): void
    {
        $preview = app(CriticalQueuePreviewBuilder::class)->build(
            items: [
                [
                    'priority' => 70,
                    'score' => 3,
                    'type' => 'Stock bajo',
                    'variant' => 'warning',
                    'icon' => 'bi-box-seam',
                    'title' => 'Mouse',
                    'subtitle' => 'Dell',
                    'detail' => '1 / 4',
                    'detailHint' => 'Faltan 3',
                    'location' => null,
                    'actor' => null,
                    'href' => '/inventory/products/3',
                ],
                [
                    'priority' => 100,
                    'score' => 1,
                    'type' => 'Prestamo vencido',
                    'variant' => 'danger',
                    'icon' => 'bi-clock-history',
                    'title' => 'LAP-001',
                    'subtitle' => 'Laptop',
                    'detail' => '10/01/2026',
                    'detailHint' => 'Hace 1d',
                    'location' => 'TI',
                    'actor' => 'Juan Perez',
                    'href' => '/inventory/products/1/assets/1',
                ],
                [
                    'priority' => 100,
                    'score' => 5,
                    'type' => 'Prestamo vencido',
                    'variant' => 'danger',
                    'icon' => 'bi-clock-history',
                    'title' => 'LAP-002',
                    'subtitle' => 'Laptop',
                    'detail' => '06/01/2026',
                    'detailHint' => 'Hace 5d',
                    'location' => 'TI',
                    'actor' => 'Ana Ruiz',
                    'href' => '/inventory/products/1/assets/2',
                ],
            ],
            limit: 10,
        );

        $this->assertSame([
            [
                'type' => 'Prestamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'LAP-002',
                'subtitle' => 'Laptop',
                'detail' => '06/01/2026',
                'detailHint' => 'Hace 5d',
                'location' => 'TI',
                'actor' => 'Ana Ruiz',
                'href' => '/inventory/products/1/assets/2',
            ],
            [
                'type' => 'Prestamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'LAP-001',
                'subtitle' => 'Laptop',
                'detail' => '10/01/2026',
                'detailHint' => 'Hace 1d',
                'location' => 'TI',
                'actor' => 'Juan Perez',
                'href' => '/inventory/products/1/assets/1',
            ],
            [
                'type' => 'Stock bajo',
                'variant' => 'warning',
                'icon' => 'bi-box-seam',
                'title' => 'Mouse',
                'subtitle' => 'Dell',
                'detail' => '1 / 4',
                'detailHint' => 'Faltan 3',
                'location' => null,
                'actor' => null,
                'href' => '/inventory/products/3',
            ],
        ], $preview);
    }

    #[Test]
    public function it_falls_back_to_the_default_limit_when_the_requested_limit_is_not_positive(): void
    {
        $items = [];

        for ($score = 1; $score <= 12; $score++) {
            $items[] = [
                'priority' => 100,
                'score' => $score,
                'type' => 'Prestamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => "LAP-{$score}",
                'subtitle' => 'Laptop',
                'detail' => null,
                'detailHint' => null,
                'location' => null,
                'actor' => null,
                'href' => "/inventory/products/1/assets/{$score}",
            ];
        }

        $preview = app(CriticalQueuePreviewBuilder::class)->build(
            items: $items,
            limit: 0,
        );

        $this->assertCount(10, $preview);
        $this->assertSame('LAP-12', $preview[0]['title']);
        $this->assertSame('LAP-3', $preview[9]['title']);
    }
}
