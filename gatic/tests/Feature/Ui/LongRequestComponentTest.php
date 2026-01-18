<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Tests for the <x-ui.long-request /> component.
 *
 * Covers Story 1.9 AC3: Operaciones lentas (>3s): skeleton/loader + progreso + cancelar
 */
class LongRequestComponentTest extends TestCase
{
    public function test_long_request_component_renders_with_required_markup(): void
    {
        $html = Blade::render('<x-ui.long-request />');

        // Verifica atributo principal para JS hook
        $this->assertStringContainsString('data-gatic-long-request', $html);

        // Verifica spinner/loader
        $this->assertStringContainsString('spinner-border', $html);

        // Verifica botón "Cancelar" con data attribute para JS
        $this->assertStringContainsString('data-gatic-long-request-cancel', $html);
        $this->assertStringContainsString('Cancelar', $html);

        // Verifica mensaje explicativo en español
        $this->assertStringContainsString('tardando', $html);

        // Verifica accesibilidad
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live', $html);
    }

    public function test_long_request_component_supports_target_attribute(): void
    {
        $html = Blade::render('<x-ui.long-request target="searchProducts" />');

        // Verifica que el target se renderiza correctamente
        $this->assertStringContainsString('data-gatic-long-request-target="searchProducts"', $html);
    }

    public function test_long_request_component_without_target_has_no_target_attribute(): void
    {
        $html = Blade::render('<x-ui.long-request />');

        // Sin target, no debe tener el atributo
        $this->assertStringNotContainsString('data-gatic-long-request-target', $html);
    }

    public function test_long_request_component_includes_skeleton(): void
    {
        $html = Blade::render('<x-ui.long-request />');

        // Verifica que incluye skeleton lines (placeholder de Bootstrap)
        $this->assertStringContainsString('placeholder', $html);
    }

    public function test_long_request_component_starts_hidden(): void
    {
        $html = Blade::render('<x-ui.long-request />');

        // El overlay debe iniciar oculto (d-none) hasta que JS lo active
        $this->assertStringContainsString('d-none', $html);
    }
}
