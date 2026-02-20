<?php

namespace Tests\Feature\ErrorReports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LivewireUnhandledExceptionInLocalDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_unhandled_exception_returns_json_with_error_id_even_in_local_debug(): void
    {
        app()['env'] = 'local';
        config(['app.debug' => true]);

        Route::get('/__test/boom-livewire-local', static function () {
            throw new \RuntimeException('Boom');
        });

        $response = $this
            ->withHeader('X-Livewire', 'true')
            ->get('/__test/boom-livewire-local');

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'message',
            'error_id',
        ]);

        $errorId = $response->json('error_id');
        $this->assertIsString($errorId);
        $this->assertMatchesRegularExpression('/[0-9A-HJKMNP-TV-Z]{26}/', $errorId);

        $response->assertJson([
            'message' => 'Ocurrió un error inesperado.',
        ]);

        $responseBody = (string) $response->getContent();
        $this->assertStringNotContainsString('RuntimeException', $responseBody);
        $this->assertStringNotContainsString('Boom', $responseBody);
    }
}
