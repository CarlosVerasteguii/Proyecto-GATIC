<?php

namespace Tests\Feature\Ui;

use App\Models\Asset;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests for status-badge component consistency.
 *
 * These tests verify that the status-badge renders with the correct
 * CSS classes for each asset status, ensuring visual consistency
 * across all views where the badge is used.
 */
class StatusBadgeConsistencyTest extends TestCase
{
    #[DataProvider('statusClassProvider')]
    public function test_status_badge_renders_correct_class_for_status(string $status, string $expectedClass): void
    {
        $html = Blade::render('<x-ui.status-badge :status="$status" />', ['status' => $status]);

        $this->assertStringContainsString('status-badge', $html);
        $this->assertStringContainsString($expectedClass, $html);
        $this->assertStringContainsString($status, $html);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function statusClassProvider(): array
    {
        return [
            'Disponible' => [Asset::STATUS_AVAILABLE, 'status-badge--available'],
            'Asignado' => [Asset::STATUS_ASSIGNED, 'status-badge--assigned'],
            'Prestado' => [Asset::STATUS_LOANED, 'status-badge--loaned'],
            'Pendiente de Retiro' => [Asset::STATUS_PENDING_RETIREMENT, 'status-badge--pending'],
            'Retirado' => [Asset::STATUS_RETIRED, 'status-badge--retired'],
        ];
    }

    public function test_status_badge_includes_icon_by_default(): void
    {
        $html = Blade::render('<x-ui.status-badge :status="$status" />', [
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->assertStringContainsString('bi-check-circle-fill', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_status_badge_solid_variant_adds_modifier_class(): void
    {
        $html = Blade::render('<x-ui.status-badge :status="$status" solid />', [
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        $this->assertStringContainsString('status-badge--solid', $html);
        $this->assertStringContainsString('status-badge--assigned', $html);
    }

    public function test_status_badge_icon_can_be_disabled(): void
    {
        $html = Blade::render('<x-ui.status-badge :status="$status" :icon="false" />', [
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->assertStringNotContainsString('bi-check-circle-fill', $html);
    }

    public function test_unknown_status_uses_secondary_fallback(): void
    {
        $html = Blade::render('<x-ui.status-badge status="Unknown Status" />');

        $this->assertStringContainsString('status-badge', $html);
        $this->assertStringContainsString('Unknown Status', $html);
    }
}
