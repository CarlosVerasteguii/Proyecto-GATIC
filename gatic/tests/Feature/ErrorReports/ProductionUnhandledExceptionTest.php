<?php

namespace Tests\Feature\ErrorReports;

use App\Models\ErrorReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionUnhandledExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unhandled_exception_renders_friendly_500_with_error_id_and_persists_report(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        Route::get('/__test/boom', static function () {
            throw new \RuntimeException('Boom');
        });

        $response = $this->get('/__test/boom');
        $response->assertStatus(500);
        $response->assertSee('Error inesperado');
        $response->assertSee('data-testid="error-alert-with-id"', false);
        $response->assertDontSee('Boom');
        $response->assertDontSee('RuntimeException');

        $body = $response->getContent();
        $this->assertMatchesRegularExpression('/[0-9A-HJKMNP-TV-Z]{26}/', $body);

        preg_match('/([0-9A-HJKMNP-TV-Z]{26})/', $body, $matches);
        $errorId = $matches[1] ?? null;
        $this->assertIsString($errorId);

        $this->assertDatabaseHas('error_reports', [
            'error_id' => $errorId,
            'environment' => 'production',
            'exception_class' => \RuntimeException::class,
        ]);

        $this->assertNotNull(ErrorReport::query()->where('error_id', $errorId)->first());
    }

    public function test_abort_500_is_reported_with_error_id_and_persists_report(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        Route::get('/__test/abort-500', static function () {
            abort(500);
        });

        $response = $this->get('/__test/abort-500');
        $response->assertStatus(500);
        $response->assertSee('Error inesperado');

        $body = $response->getContent();
        $this->assertMatchesRegularExpression('/[0-9A-HJKMNP-TV-Z]{26}/', $body);

        $this->assertDatabaseCount('error_reports', 1);
    }

    public function test_error_report_does_not_persist_sensitive_referer_query_or_secrets_in_exception_message(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        Route::get('/__test/redaction', static function () {
            throw new \RuntimeException('password=supersecret token=abc123 Bearer abc.def.ghi');
        });

        $this->withHeader('referer', 'https://example.com/path?token=abc123#frag')
            ->get('/__test/redaction')
            ->assertStatus(500);

        $report = ErrorReport::query()->firstOrFail();

        $this->assertIsArray($report->context);
        $referer = $report->context['request']['headers']['referer'] ?? null;
        $this->assertSame('https://example.com/path', $referer);

        $this->assertIsString($report->exception_message);
        $this->assertStringNotContainsString('supersecret', $report->exception_message);
        $this->assertStringNotContainsString('abc123', $report->exception_message);
        $this->assertStringContainsString('[REDACTED]', $report->exception_message);
    }

    public function test_unhandled_exception_returns_json_with_error_id_for_livewire_requests(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        Route::get('/__test/boom-json', static function () {
            throw new \RuntimeException('Boom');
        });

        $response = $this
            ->withHeader('X-Livewire', 'true')
            ->get('/__test/boom-json');

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'message',
            'error_id',
        ]);
    }

    public function test_expected_authorization_exceptions_are_not_converted_to_500_in_production(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        Route::get('/__test/forbidden', static function () {
            throw new \Illuminate\Auth\Access\AuthorizationException('Forbidden');
        });

        $this->get('/__test/forbidden')->assertForbidden();
    }
}
