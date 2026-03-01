<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BadgeComponentTest extends TestCase
{
    #[DataProvider('toneProvider')]
    public function test_badge_renders_tone_and_default_variant(string $tone): void
    {
        $html = Blade::render('<x-ui.badge :tone="$tone">Etiqueta</x-ui.badge>', [
            'tone' => $tone,
        ]);

        $this->assertStringContainsString('gatic-badge', $html);
        $this->assertStringContainsString('gatic-badge--tone-'.$tone, $html);
        $this->assertStringContainsString('gatic-badge--variant-default', $html);
        $this->assertStringContainsString('Etiqueta', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function toneProvider(): array
    {
        return [
            'neutral' => ['neutral'],
            'success' => ['success'],
            'info' => ['info'],
            'warning' => ['warning'],
            'danger' => ['danger'],
            'primary' => ['primary'],
        ];
    }

    public function test_badge_compact_variant_renders_modifier_class(): void
    {
        $html = Blade::render('<x-ui.badge tone="success" variant="compact">Compact</x-ui.badge>');

        $this->assertStringContainsString('gatic-badge--variant-compact', $html);
    }

    public function test_badge_solid_variant_defaults_to_no_rail(): void
    {
        $html = Blade::render('<x-ui.badge tone="success" variant="solid">Solid</x-ui.badge>');

        $this->assertStringContainsString('gatic-badge--variant-solid', $html);
        $this->assertStringContainsString('gatic-badge--no-rail', $html);
    }

    public function test_badge_can_disable_rail_explicitly(): void
    {
        $html = Blade::render('<x-ui.badge tone="neutral" :withRail="false">Sin rail</x-ui.badge>');

        $this->assertStringContainsString('gatic-badge--no-rail', $html);
    }

    public function test_badge_renders_icon_as_decorative(): void
    {
        $html = Blade::render('<x-ui.badge tone="info" icon="bi-info-circle-fill">Info</x-ui.badge>');

        $this->assertStringContainsString('bi-info-circle-fill', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_badge_adds_role_status_only_when_aria_live_polite(): void
    {
        $html = Blade::render('<x-ui.badge tone="info" ariaLive="polite">Actualizando</x-ui.badge>');

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
    }

    public function test_badge_does_not_add_role_status_by_default(): void
    {
        $html = Blade::render('<x-ui.badge tone="info">Info</x-ui.badge>');

        $this->assertStringNotContainsString('role="status"', $html);
        $this->assertStringNotContainsString('aria-live="polite"', $html);
    }

    public function test_badge_button_defaults_type_button(): void
    {
        $html = Blade::render('<x-ui.badge tone="warning" as="button">Accion</x-ui.badge>');

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('gatic-badge--interactive', $html);
    }

    public function test_badge_scss_contains_core_tone_and_variant_selectors(): void
    {
        $scss = (string) file_get_contents(base_path('resources/sass/_badges.scss'));

        foreach ([
            '.gatic-badge--tone-neutral',
            '.gatic-badge--tone-secondary',
            '.gatic-badge--tone-info',
            '.gatic-badge--tone-success',
            '.gatic-badge--tone-warning',
            '.gatic-badge--tone-danger',
            '.gatic-badge--tone-primary',
            '.gatic-badge--tone-role-admin',
            '.gatic-badge--tone-role-editor',
            '.gatic-badge--tone-role-lector',
            '.gatic-badge--tone-status-available',
            '.gatic-badge--tone-status-loaned',
            '.gatic-badge--tone-status-assigned',
            '.gatic-badge--tone-status-pending',
            '.gatic-badge--tone-status-retired',
            '.gatic-badge--variant-compact',
            '.gatic-badge--variant-solid',
        ] as $expectedSelector) {
            $this->assertStringContainsString($expectedSelector, $scss);
        }
    }
}
