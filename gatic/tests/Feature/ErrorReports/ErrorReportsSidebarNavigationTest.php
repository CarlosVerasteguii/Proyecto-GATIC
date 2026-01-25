<?php

namespace Tests\Feature\ErrorReports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorReportsSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_error_reports_link_in_sidebar(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Errores (soporte)')
            ->assertSee(route('admin.error-reports.lookup'), false);
    }

    public function test_editor_does_not_see_error_reports_link_in_sidebar(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Errores (soporte)')
            ->assertDontSee(route('admin.error-reports.lookup'), false);
    }

    public function test_lector_does_not_see_error_reports_link_in_sidebar(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Errores (soporte)')
            ->assertDontSee(route('admin.error-reports.lookup'), false);
    }
}
