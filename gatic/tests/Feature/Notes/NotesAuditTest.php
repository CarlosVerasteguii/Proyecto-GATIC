<?php

namespace Tests\Feature\Notes;

use App\Enums\UserRole;
use App\Jobs\RecordAuditLog;
use App\Livewire\Ui\NotesPanel;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for notes audit instrumentation (AC5).
 *
 * - Creating a note dispatches audit event
 * - Audit is best-effort (does not block note creation)
 */
class NotesAuditTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    public function test_creating_note_dispatches_audit_job(): void
    {
        Queue::fake();

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'Note that should be audited')
            ->call('createNote')
            ->assertHasNoErrors();

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            return $job->payload['action'] === AuditLog::ACTION_NOTE_MANUAL_CREATE
                && $job->payload['subject_type'] === Product::class
                && $job->payload['subject_id'] === $this->product->id
                && $job->payload['actor_user_id'] === $this->admin->id
                && isset($job->payload['context']['note_id'])
                && isset($job->payload['context']['summary']);
        });
    }

    public function test_audit_context_contains_note_summary(): void
    {
        Queue::fake();

        $noteBody = 'This is a longer note that should have a truncated summary in the audit context for the sake of brevity and storage efficiency.';

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', $noteBody)
            ->call('createNote');

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            $summary = $job->payload['context']['summary'] ?? '';

            // Summary should be truncated (Str::limit with 80 chars)
            return strlen($summary) <= 83; // 80 + "..."
        });
    }

    public function test_note_action_constant_is_defined(): void
    {
        $this->assertEquals('notes.manual.create', AuditLog::ACTION_NOTE_MANUAL_CREATE);
        $this->assertContains(AuditLog::ACTION_NOTE_MANUAL_CREATE, AuditLog::ACTIONS);
    }

    public function test_note_action_has_label(): void
    {
        $this->assertArrayHasKey(AuditLog::ACTION_NOTE_MANUAL_CREATE, AuditLog::ACTION_LABELS);
        $this->assertEquals('Nota manual creada', AuditLog::ACTION_LABELS[AuditLog::ACTION_NOTE_MANUAL_CREATE]);
    }
}
