<?php

namespace Tests\Feature\ErrorReports;

use App\Models\ErrorReport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorReportPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_persist_error_report_with_unique_error_id(): void
    {
        $report = ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'testing',
            'method' => 'GET',
            'url' => 'http://localhost/foo',
            'exception_class' => 'RuntimeException',
            'exception_message' => 'Boom',
            'stack_trace' => 'trace',
            'context' => ['request' => ['route' => 'foo']],
        ]);

        $this->assertGreaterThan(0, $report->id);

        $this->assertDatabaseHas('error_reports', [
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'testing',
            'method' => 'GET',
        ]);
    }

    public function test_error_id_is_unique(): void
    {
        ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'testing',
            'method' => 'GET',
            'url' => 'http://localhost/foo',
            'exception_class' => 'RuntimeException',
            'exception_message' => 'Boom',
            'stack_trace' => 'trace',
            'context' => [],
        ]);

        $this->expectException(QueryException::class);

        ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'testing',
            'method' => 'GET',
            'url' => 'http://localhost/bar',
            'exception_class' => 'RuntimeException',
            'exception_message' => 'Boom again',
            'stack_trace' => 'trace',
            'context' => [],
        ]);
    }
}
