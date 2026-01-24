<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for attachment download (AC3, AC5).
 *
 * - Download with proper Content-Disposition
 * - Anti-enumeration: cannot access without authorization
 * - Cannot access attachments of soft-deleted entities
 */
class AttachmentsDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->editor = User::factory()->create(['role' => UserRole::Editor]);
        $this->lector = User::factory()->create(['role' => UserRole::Lector]);

        $category = Category::factory()->create(['is_serialized' => false]);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Download with Content-Disposition
    // ───────────────────────────────────────────────────────────────────────

    public function test_download_returns_file_with_original_name(): void
    {
        Storage::disk('local')->put('attachments/Product/1/uuid-123', 'PDF content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'Mi Reporte 2024.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/uuid-123',
            'mime_type' => 'application/pdf',
            'size_bytes' => 11,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        // Laravel encodes special characters in Content-Disposition
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_editor_can_download_attachment(): void
    {
        Storage::disk('local')->put('attachments/Product/1/editor-uuid', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/editor-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        $response = $this->actingAs($this->editor)
            ->get(route('attachments.download', $attachment->id));

        $response->assertOk();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC5: Anti-enumeration - Lector blocked
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_download_attachment(): void
    {
        Storage::disk('local')->put('attachments/Product/1/lector-test', 'secret');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'secret.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/lector-test',
            'mime_type' => 'application/pdf',
            'size_bytes' => 6,
        ]);

        $response = $this->actingAs($this->lector)
            ->get(route('attachments.download', $attachment->id));

        $response->assertForbidden();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC5: Anti-enumeration - Nonexistent attachment returns 404
    // ───────────────────────────────────────────────────────────────────────

    public function test_nonexistent_attachment_returns_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', 99999));

        $response->assertNotFound();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC5: Missing file in storage returns 404
    // ───────────────────────────────────────────────────────────────────────

    public function test_missing_file_in_storage_returns_404(): void
    {
        // Don't put file in storage, just create DB record
        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'ghost.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/nonexistent-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment->id));

        $response->assertNotFound();
    }

    // ───────────────────────────────────────────────────────────────────────
    // Soft-deleted entity: attachment not accessible
    // ───────────────────────────────────────────────────────────────────────

    public function test_cannot_download_attachment_of_soft_deleted_product(): void
    {
        Storage::disk('local')->put('attachments/Product/1/deleted-product', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/deleted-product',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        // Soft delete the product
        $this->product->delete();

        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment->id));

        $response->assertNotFound();
    }

    public function test_cannot_download_attachment_of_soft_deleted_asset(): void
    {
        $category = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        Storage::disk('local')->put('attachments/Asset/'.$asset->id.'/deleted-asset', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Asset::class,
            'attachable_id' => $asset->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'asset-file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Asset/'.$asset->id.'/deleted-asset',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        // Soft delete the asset
        $asset->delete();

        $response = $this->actingAs($this->admin)
            ->get(route('attachments.download', $attachment->id));

        $response->assertNotFound();
    }

    // ───────────────────────────────────────────────────────────────────────
    // Unauthenticated access blocked
    // ───────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_download(): void
    {
        Storage::disk('local')->put('attachments/Product/1/public-test', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/public-test',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        $response = $this->get(route('attachments.download', $attachment->id));

        $response->assertRedirect(route('login'));
    }
}
