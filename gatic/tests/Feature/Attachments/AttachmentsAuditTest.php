<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Jobs\RecordAuditLog;
use App\Livewire\Ui\AttachmentsPanel;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for attachment audit events (AC6).
 *
 * - Upload dispatches audit job
 * - Delete dispatches audit job
 * - Context respects allowlist (no file content)
 */
class AttachmentsAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => false]);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC6: Upload dispatches audit job
    // ───────────────────────────────────────────────────────────────────────

    public function test_upload_dispatches_audit_job(): void
    {
        $file = UploadedFile::fake()->create('audit-test.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $payload = $job->payload;

            return $payload['action'] === AuditLog::ACTION_ATTACHMENT_UPLOAD
                && $payload['subject_type'] === Product::class
                && $payload['subject_id'] === $this->product->id
                && $payload['actor_user_id'] === $this->admin->id;
        });
    }

    public function test_upload_audit_context_contains_attachment_id(): void
    {
        $file = UploadedFile::fake()->create('context-test.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment');

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $context = $job->payload['context'] ?? [];

            return isset($context['attachment_id'])
                && isset($context['summary'])
                && isset($context['product_id']);
        });
    }

    public function test_upload_audit_context_contains_filename_summary(): void
    {
        $file = UploadedFile::fake()->create('my-important-document.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment');

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $context = $job->payload['context'] ?? [];

            return isset($context['summary'])
                && str_contains($context['summary'], 'my-important-document.pdf');
        });
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC6: Delete dispatches audit job
    // ───────────────────────────────────────────────────────────────────────

    public function test_delete_dispatches_audit_job(): void
    {
        Storage::disk('local')->put('attachments/Product/1/audit-delete', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'delete-audit-test.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/audit-delete',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachment->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $payload = $job->payload;

            return $payload['action'] === AuditLog::ACTION_ATTACHMENT_DELETE
                && $payload['subject_type'] === Product::class
                && $payload['subject_id'] === $this->product->id;
        });
    }

    public function test_delete_audit_context_contains_filename_summary(): void
    {
        Storage::disk('local')->put('attachments/Product/1/summary-test', 'content');

        $attachment = Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'deleted-file-name.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/summary-test',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->call('deleteAttachment', $attachment->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $context = $job->payload['context'] ?? [];

            return isset($context['summary'])
                && isset($context['attachment_id'])
                && str_contains($context['summary'], 'deleted-file-name.pdf');
        });
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC6: Context does NOT contain file content
    // ───────────────────────────────────────────────────────────────────────

    public function test_audit_context_does_not_contain_file_content(): void
    {
        $file = UploadedFile::fake()->create('secret-content.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment');

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $context = $job->payload['context'] ?? [];

            // Should NOT have file content, path details, or other sensitive data
            $this->assertArrayNotHasKey('content', $context);
            $this->assertArrayNotHasKey('file_content', $context);
            $this->assertArrayNotHasKey('path', $context);
            $this->assertArrayNotHasKey('disk_path', $context);

            return true;
        });
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC6: Audit is best-effort (upload succeeds even if queue fails)
    // ───────────────────────────────────────────────────────────────────────

    public function test_upload_succeeds_even_when_audit_fails(): void
    {
        // Make Queue::fake throw on push to simulate failure
        Queue::shouldReceive('push')->andThrow(new \Exception('Queue failure'));
        Queue::shouldReceive('connection')->andReturnSelf();

        $file = UploadedFile::fake()->create('resilient.pdf', 500, 'application/pdf');

        // This should still succeed despite audit failure
        Livewire::actingAs($this->admin)
            ->test(AttachmentsPanel::class, [
                'attachableType' => Product::class,
                'attachableId' => $this->product->id,
            ])
            ->set('newFile', $file)
            ->call('uploadAttachment')
            ->assertSet('showSuccessMessage', true);

        // Attachment should still be created
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
        ]);
    }
}
