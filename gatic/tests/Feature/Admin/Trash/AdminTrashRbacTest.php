<?php

namespace Tests\Feature\Admin\Trash;

use App\Enums\UserRole;
use App\Livewire\Admin\Trash\TrashIndex;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 8.4: RBAC tests for admin trash functionality.
 */
class AdminTrashRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_trash_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/trash')
            ->assertOk();
    }

    public function test_editor_cannot_access_admin_trash_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/admin/trash')
            ->assertForbidden();
    }

    public function test_lector_cannot_access_admin_trash_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/admin/trash')
            ->assertForbidden();
    }

    public function test_editor_cannot_execute_restore_action(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor);

        $component = new TrashIndex;

        try {
            $component->restore('products', 1);
            $this->fail('Expected AuthorizationException for restore().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_editor_cannot_execute_purge_action(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor);

        $component = new TrashIndex;

        try {
            $component->purge('products', 1);
            $this->fail('Expected AuthorizationException for purge().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_editor_cannot_execute_empty_trash_action(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor);

        $component = new TrashIndex;

        try {
            $component->emptyTrash();
            $this->fail('Expected AuthorizationException for emptyTrash().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }
}
