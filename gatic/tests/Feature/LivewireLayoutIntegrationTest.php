<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewireLayoutIntegrationTest extends TestCase
{
    public function test_app_layout_includes_livewire_assets_in_expected_locations(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $stylesPos = strpos($contents, '@livewireStyles');
        $scriptsPos = strpos($contents, '@livewireScripts');
        $headClosePos = strpos($contents, '</head>');
        $bodyClosePos = strpos($contents, '</body>');

        $this->assertNotFalse($stylesPos);
        $this->assertNotFalse($scriptsPos);
        $this->assertNotFalse($headClosePos);
        $this->assertNotFalse($bodyClosePos);

        $this->assertLessThan($headClosePos, $stylesPos);
        $this->assertLessThan($bodyClosePos, $scriptsPos);

        $stackScriptsPos = strpos($contents, "@stack('scripts')");
        if ($stackScriptsPos !== false) {
            $this->assertLessThan($stackScriptsPos, $scriptsPos);
        }

        $this->assertStringContainsString('<x-ui.toast-container', $contents);
    }

    public function test_guest_layout_includes_livewire_assets_in_expected_locations(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/guest.blade.php'));

        $stylesPos = strpos($contents, '@livewireStyles');
        $scriptsPos = strpos($contents, '@livewireScripts');
        $headClosePos = strpos($contents, '</head>');
        $bodyClosePos = strpos($contents, '</body>');

        $this->assertNotFalse($stylesPos);
        $this->assertNotFalse($scriptsPos);
        $this->assertNotFalse($headClosePos);
        $this->assertNotFalse($bodyClosePos);

        $this->assertLessThan($headClosePos, $stylesPos);
        $this->assertLessThan($bodyClosePos, $scriptsPos);

        $this->assertStringContainsString('<x-ui.toast-container', $contents);
    }
}
