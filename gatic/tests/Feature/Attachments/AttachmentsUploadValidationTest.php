<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Livewire\Ui\AttachmentsPanel;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for attachment upload validation (AC2).
 *
 * - Type validation: only allowed MIME types
 * - Size validation: max 10MB
 * - Storage correctness: UUID filename, private disk
 */
class AttachmentsUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => false]);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC2: Valid file types
    // ───────────────────────────────────────────────────────────────────────

    public function test_can_upload_pdf(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_can_upload_png_image(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 100, 100);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'original_name' => 'photo.png',
        ]);
    }

    public function test_can_upload_jpeg_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();
    }

    public function test_can_upload_txt_file(): void
    {
        $file = UploadedFile::fake()->create('readme.txt', 100, 'text/plain');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC2: Invalid file types - rejected
    // ───────────────────────────────────────────────────────────────────────

    public function test_rejects_php_file(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasErrors(['newFile']);

        $this->assertDatabaseMissing('attachments', [
            'attachable_id' => $this->product->id,
        ]);
    }

    public function test_rejects_exe_file(): void
    {
        $file = UploadedFile::fake()->create('program.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasErrors(['newFile']);
    }

    public function test_rejects_js_file(): void
    {
        $file = UploadedFile::fake()->create('script.js', 100, 'application/javascript');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasErrors(['newFile']);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC2: Size validation
    // ───────────────────────────────────────────────────────────────────────

    public function test_rejects_file_exceeding_max_size(): void
    {
        // Create a file larger than 10MB (10240 KB)
        $file = UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasErrors(['newFile']);
    }

    public function test_accepts_file_at_max_size(): void
    {
        // Create a file just under 10MB
        $file = UploadedFile::fake()->create('large.pdf', 10200, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC1: Storage correctness - UUID filename
    // ───────────────────────────────────────────────────────────────────────

    public function test_stores_file_with_uuid_path(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $attachment = Attachment::first();

        // Path should be attachments/Product/{id}/{uuid} format
        $this->assertStringStartsWith('attachments/Product/', $attachment->path);

        // Path should NOT contain the original filename
        $this->assertStringNotContainsString('report.pdf', $attachment->path);

        // Original name should be preserved in DB
        $this->assertEquals('report.pdf', $attachment->original_name);

        // File should exist in storage
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_preserves_original_filename_in_database(): void
    {
        $file = UploadedFile::fake()->create('mi archivo con espacios.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'original_name' => 'mi archivo con espacios.pdf',
        ]);
    }

    public function test_stores_correct_metadata(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1234, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment');

        $attachment = Attachment::first();

        $this->assertEquals('local', $attachment->disk);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertEquals($this->admin->id, $attachment->uploaded_by_user_id);
        $this->assertGreaterThan(0, $attachment->size_bytes);
    }

    public function test_upload_cleans_up_file_when_database_insert_fails(): void
    {
        $file = UploadedFile::fake()->create('cleanup.pdf', 500, 'application/pdf');

        $userId = $this->admin->id;
        $this->admin->delete();

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertSet('showErrorMessage', true);

        $this->assertDatabaseMissing('attachments', [
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $userId,
            'original_name' => 'cleanup.pdf',
        ]);

        $storedFiles = Storage::disk('local')->allFiles('attachments/Product/'.$this->product->id);
        $this->assertCount(0, $storedFiles);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC2: Required file validation
    // ───────────────────────────────────────────────────────────────────────

    public function test_requires_file_to_upload(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('uploadAttachment')
            ->assertHasErrors(['newFile' => 'required']);
    }
}
