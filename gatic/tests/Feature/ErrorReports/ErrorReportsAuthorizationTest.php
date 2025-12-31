<?php

namespace Tests\Feature\ErrorReports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorReportsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_error_reports_lookup_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/error-reports')
            ->assertOk();
    }

    public function test_editor_cannot_access_error_reports_lookup_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/admin/error-reports')
            ->assertForbidden();
    }

    public function test_lector_cannot_access_error_reports_lookup_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/admin/error-reports')
            ->assertForbidden();
    }
}
