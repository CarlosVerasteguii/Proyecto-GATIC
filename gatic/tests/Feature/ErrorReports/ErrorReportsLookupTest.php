<?php

namespace Tests\Feature\ErrorReports;

use App\Livewire\Admin\ErrorReports\ErrorReportsLookup;
use App\Models\ErrorReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErrorReportsLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
    }

    public function test_admin_can_find_an_error_report_by_error_id(): void
    {
        $editor = User::factory()->create(['role' => 'Editor', 'name' => 'Editor User']);

        $report = ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'production',
            'user_id' => $editor->id,
            'user_role' => 'Editor',
            'method' => 'GET',
            'url' => 'http://localhost:8080/inventory/products',
            'route' => 'inventory.products.index',
            'exception_class' => \RuntimeException::class,
            'exception_message' => 'Boom',
            'stack_trace' => "line one\nline two",
            'context' => [
                'request' => [
                    'path' => '/inventory/products',
                ],
            ],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ErrorReportsLookup::class)
            ->set('errorId', '01JH2J3W3M5P7G9R8C1V2B3N4M')
            ->call('search')
            ->assertSet('reportId', $report->id)
            ->assertSee('Error encontrado')
            ->assertSee($report->error_id)
            ->assertSee($report->exception_class)
            ->assertSee('line one');
    }

    public function test_blank_lookup_shows_validation_error_instead_of_not_found_state(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ErrorReportsLookup::class)
            ->set('errorId', '   ')
            ->call('search')
            ->assertHasErrors(['errorId' => 'required'])
            ->assertSet('searched', false)
            ->assertSet('reportId', null)
            ->assertDontSee('No se encontró un error con ese ID');
    }

    public function test_lookup_resets_previous_result_when_input_changes(): void
    {
        $report = ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'production',
            'exception_class' => \RuntimeException::class,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ErrorReportsLookup::class)
            ->set('errorId', $report->error_id)
            ->call('search')
            ->assertSet('searched', true)
            ->assertSet('reportId', $report->id)
            ->set('errorId', '01JH2J3W3M5P7G9R8C1V2B3N4X')
            ->assertSet('searched', false)
            ->assertSet('reportId', null);
    }

    public function test_lookup_can_bootstrap_from_query_string_error_id(): void
    {
        $report = ErrorReport::query()->create([
            'error_id' => '01JH2J3W3M5P7G9R8C1V2B3N4M',
            'environment' => 'production',
            'exception_class' => \RuntimeException::class,
        ]);

        Livewire::withQueryParams(['error' => $report->error_id])
            ->actingAs($this->admin)
            ->test(ErrorReportsLookup::class)
            ->assertSet('errorId', $report->error_id)
            ->assertSet('searched', true)
            ->assertSet('reportId', $report->id)
            ->assertSee($report->error_id);
    }
}
