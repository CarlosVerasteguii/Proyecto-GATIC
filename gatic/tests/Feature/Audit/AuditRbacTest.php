<?php

namespace Tests\Feature\Audit;

use App\Livewire\Admin\Audit\AuditLogsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for Audit module RBAC (AC4).
 *
 * - Admin can access audit module
 * - Editor/Lector are blocked (403)
 */
class AuditRbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
        $this->editor = User::factory()->create(['role' => 'Editor', 'name' => 'Editor User']);
        $this->lector = User::factory()->create(['role' => 'Lector', 'name' => 'Lector User']);
    }

    public function test_admin_can_access_audit_module(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSuccessful()
            ->assertSee('Registro de Auditoría');
    }

    public function test_editor_cannot_access_audit_module(): void
    {
        Livewire::actingAs($this->editor)
            ->test(AuditLogsIndex::class)
            ->assertForbidden();
    }

    public function test_lector_cannot_access_audit_module(): void
    {
        Livewire::actingAs($this->lector)
            ->test(AuditLogsIndex::class)
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_audit_route(): void
    {
        $response = $this->get(route('admin.audit.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_audit_route(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit.index'));

        $response->assertSuccessful();
        $response->assertSee('Registro de Auditoría');
    }

    public function test_editor_gets_403_on_audit_route(): void
    {
        $response = $this->actingAs($this->editor)->get(route('admin.audit.index'));

        $response->assertForbidden();
    }

    public function test_lector_gets_403_on_audit_route(): void
    {
        $response = $this->actingAs($this->lector)->get(route('admin.audit.index'));

        $response->assertForbidden();
    }
}
