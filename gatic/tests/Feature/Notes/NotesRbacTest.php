<?php

namespace Tests\Feature\Notes;

use App\Enums\UserRole;
use App\Livewire\Ui\NotesPanel;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for notes RBAC (AC2, AC3).
 *
 * - View: follows entity visibility (inventory.view for Product/Asset)
 * - Create: Admin/Editor only (notes.manage)
 * - Lector cannot create notes
 */
class NotesRbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    private Product $product;

    private Asset $asset;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->editor = User::factory()->create(['role' => UserRole::Editor]);
        $this->lector = User::factory()->create(['role' => UserRole::Lector]);

        $category = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id]);
        $this->asset = Asset::factory()->create([
            'product_id' => $this->product->id,
            'location_id' => $location->id,
        ]);
        $this->employee = Employee::factory()->create();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Create note - Admin can create
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_can_create_note_on_product(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'This is a test note from admin')
            ->call('createNote')
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertDatabaseHas('notes', [
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
            'author_user_id' => $this->admin->id,
            'body' => 'This is a test note from admin',
        ]);
    }

    public function test_admin_can_create_note_on_asset(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Asset::class,
                'noteableId' => $this->asset->id,
            ])
            ->set('newNoteBody', 'Asset note from admin')
            ->call('createNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'noteable_type' => Asset::class,
            'noteable_id' => $this->asset->id,
            'author_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_note_on_employee(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Employee::class,
                'noteableId' => $this->employee->id,
            ])
            ->set('newNoteBody', 'Employee note from admin')
            ->call('createNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'noteable_type' => Employee::class,
            'noteable_id' => $this->employee->id,
            'author_user_id' => $this->admin->id,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Create note - Editor can create
    // ───────────────────────────────────────────────────────────────────────

    public function test_editor_can_create_note_on_product(): void
    {
        Livewire::actingAs($this->editor)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'Note from editor')
            ->call('createNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
            'author_user_id' => $this->editor->id,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Create note - Lector cannot create (403)
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_create_note(): void
    {
        Livewire::actingAs($this->lector)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'Attempted note from lector')
            ->call('createNote')
            ->assertForbidden();

        $this->assertDatabaseMissing('notes', [
            'author_user_id' => $this->lector->id,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC2: View notes - follows entity visibility
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_can_view_notes_on_product(): void
    {
        $this->product->notes()->create([
            'author_user_id' => $this->admin->id,
            'body' => 'Existing note',
        ]);

        Livewire::actingAs($this->lector)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->assertSee('Existing note')
            ->assertSee($this->admin->name);
    }

    public function test_lector_can_view_notes_on_asset(): void
    {
        $this->asset->notes()->create([
            'author_user_id' => $this->editor->id,
            'body' => 'Asset note visible to lector',
        ]);

        Livewire::actingAs($this->lector)
            ->test(NotesPanel::class, [
                'noteableType' => Asset::class,
                'noteableId' => $this->asset->id,
            ])
            ->assertSee('Asset note visible to lector');
    }

    public function test_lector_cannot_access_employee_notes(): void
    {
        // Employee view requires inventory.manage, which Lector does not have
        Livewire::actingAs($this->lector)
            ->test(NotesPanel::class, [
                'noteableType' => Employee::class,
                'noteableId' => $this->employee->id,
            ])
            ->assertForbidden();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: UI hides create form for Lector
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_does_not_see_create_form(): void
    {
        $component = Livewire::actingAs($this->lector)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        $this->assertFalse($component->get('canCreate'));
        $component->assertDontSee('Guardar nota');
    }

    public function test_admin_sees_create_form(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        $this->assertTrue($component->get('canCreate'));
        $component->assertSee('Guardar nota');
    }

    public function test_editor_sees_create_form(): void
    {
        $component = Livewire::actingAs($this->editor)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        $this->assertTrue($component->get('canCreate'));
    }
}
