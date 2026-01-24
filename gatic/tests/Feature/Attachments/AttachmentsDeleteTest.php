<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Livewire\Ui\AttachmentsPanel;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for attachment deletion (AC4).
 *
 * - Deletes DB record and file from storage
 * - Handles gracefully when file is already missing
 * - Shows appropriate messages
 */
class AttachmentsDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->editor = User::factory()->create(['role' => UserRole::Editor]);

        $category = Category::factory()->create(['is_serialized' => false]);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC4: Delete removes DB record and file
    // ───────────────────────────────────────────────────────────────────────

    public function test_delete_removes_database_record(): void
    {
        Storage::disk('local')->put('attachments/Product/1/delete-test', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'to-delete.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/delete-test',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        $attachmentId = $attachment->id;

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachmentId)
            ->assertSet('showSuccessMessage', true)
            ->assertSee('Adjunto eliminado correctamente');

        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
    }

    public function test_delete_removes_file_from_storage(): void
    {
        $path = 'attachments/Product/1/storage-delete-test';
        Storage::disk('local')->put($path, 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'storage-file.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        Storage::disk('local')->assertExists($path);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachment->id);

        Storage::disk('local')->assertMissing($path);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC4: Graceful handling when file already missing
    // ───────────────────────────────────────────────────────────────────────

    public function test_delete_succeeds_even_if_file_already_missing(): void
    {
        // Create attachment record but don't put file in storage
        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'ghost-file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/nonexistent-file',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $attachmentId = $attachment->id;

        // Should still succeed (clean up orphaned DB record)
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachmentId)
            ->assertSet('showSuccessMessage', true);

        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC4: Cannot delete nonexistent attachment
    // ───────────────────────────────────────────────────────────────────────

    public function test_delete_nonexistent_attachment_shows_error(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', 99999)
            ->assertSet('showErrorMessage', true)
            ->assertSee('Adjunto no encontrado');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Editor can delete
    // ───────────────────────────────────────────────────────────────────────

    public function test_editor_can_delete_attachment(): void
    {
        Storage::disk('local')->put('attachments/Product/1/editor-delete', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'editor-delete.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/editor-delete',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        Livewire::actingAs($this->editor)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('showSuccessMessage', true);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Multiple attachments - delete only the specified one
    // ───────────────────────────────────────────────────────────────────────

    public function test_delete_only_affects_specified_attachment(): void
    {
        Storage::disk('local')->put('attachments/Product/1/keep-this', 'keep');
        Storage::disk('local')->put('attachments/Product/1/delete-this', 'delete');

        $keepAttachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'keep.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/keep-this',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4,
        ]);

        $deleteAttachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'delete.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/delete-this',
            'mime_type' => 'application/pdf',
            'size_bytes' => 6,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $deleteAttachment->id);

        // Deleted attachment should be gone
        $this->assertDatabaseMissing('attachments', ['id' => $deleteAttachment->id]);
        Storage::disk('local')->assertMissing('attachments/Product/1/delete-this');

        // Kept attachment should still exist
        $this->assertDatabaseHas('attachments', ['id' => $keepAttachment->id]);
        Storage::disk('local')->assertExists('attachments/Product/1/keep-this');
    }
}
