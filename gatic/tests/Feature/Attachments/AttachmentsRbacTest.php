<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Livewire\Ui\AttachmentsPanel;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for attachments RBAC (AC3, AC5).
 *
 * - View/Download: Admin/Editor only (attachments.view)
 * - Upload/Delete: Admin/Editor only (attachments.manage)
 * - Lector cannot access attachments at all
 */
class AttachmentsRbacTest extends TestCase
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

        Storage::fake('local');

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
    // AC3: View attachments - Admin can view
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_attachments_panel_on_product(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->assertOk()
            ->assertSee('Adjuntos');
    }

    public function test_admin_can_view_attachments_panel_on_asset(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Asset::class,
                'attachableId' => $this->asset->id,
            ])
            ->assertOk();
    }

    public function test_admin_can_view_attachments_panel_on_employee(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Employee::class,
                'attachableId' => $this->employee->id,
            ])
            ->assertOk();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: View attachments - Editor can view
    // ───────────────────────────────────────────────────────────────────────

    public function test_editor_can_view_attachments_panel(): void
    {
        Livewire::actingAs($this->editor)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->assertOk();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: View attachments - Lector CANNOT view (403)
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_view_attachments_panel(): void
    {
        Livewire::actingAs($this->lector)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->assertForbidden();
    }

    public function test_lector_cannot_view_attachments_panel_on_asset(): void
    {
        Livewire::actingAs($this->lector)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Asset::class,
                'attachableId' => $this->asset->id,
            ])
            ->assertForbidden();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC1: Upload - Admin can upload
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_can_upload_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_editor_can_upload_attachment(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 100, 100);

        Livewire::actingAs($this->editor)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->editor->id,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC1: Upload - Lector CANNOT upload (403)
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_upload_attachment(): void
    {
        // Lector cannot even mount the component
        Livewire::actingAs($this->lector)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attachments', [
            'uploaded_by_user_id' => $this->lector->id,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC4: Delete - Admin can delete
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_attachment(): void
    {
        // Create an attachment first
        Storage::disk('local')->put('attachments/Product/1/test-uuid', 'dummy content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->editor->id,
            'original_name' => 'test.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/test-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('showSuccessMessage', true);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_editor_can_delete_attachment(): void
    {
        Storage::disk('local')->put('attachments/Product/1/test-uuid-2', 'dummy content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'test2.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/test-uuid-2',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
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
    // AC3: Download - Lector cannot download via route
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_download_attachment_via_route(): void
    {
        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'secret.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/secret-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);

        $this->actingAs($this->lector)
            ->get(route('attachments.download', $attachment->id))
            ->assertForbidden();
    }

    public function test_admin_can_download_attachment_via_route(): void
    {
        Storage::disk('local')->put('attachments/Product/1/downloadable-uuid', 'PDF content here');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'report.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/downloadable-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 16,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment->id));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=report.pdf');
    }

    // ───────────────────────────────────────────────────────────────────────
    // UI: Lector does not see manage controls
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_sees_upload_form(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ]);

        $this->assertTrue($component->get('canManage'));
        $component->assertSee('Subir adjunto');
    }

    public function test_editor_sees_upload_form(): void
    {
        $component = Livewire::actingAs($this->editor)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ]);

        $this->assertTrue($component->get('canManage'));
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC5: Anti-enumeration - cannot access attachment from other entity
    // ───────────────────────────────────────────────────────────────────────

    public function test_cannot_delete_attachment_from_different_entity(): void
    {
        // Create attachment on product
        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'product-file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/product-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);

        // Try to delete from asset panel (should fail - attachment doesn't belong to asset)
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Asset::class,
                'attachableId' => $this->asset->id,
            ])
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('showErrorMessage', true);

        // Attachment should still exist
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }
}
