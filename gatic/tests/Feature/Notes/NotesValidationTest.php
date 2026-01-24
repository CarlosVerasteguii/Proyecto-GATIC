<?php

namespace Tests\Feature\Notes;

use App\Enums\UserRole;
use App\Livewire\Ui\NotesPanel;
use App\Models\Category;
use App\Models\Note;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for notes validation (AC4).
 *
 * - Body required, min:1, max:5000
 * - Stored as plain text (no HTML)
 * - Line breaks preserved in render
 */
class NotesValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    public function test_note_body_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', '')
            ->call('createNote')
            ->assertHasErrors(['newNoteBody' => 'required']);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_note_body_maximum_length(): void
    {
        $longBody = str_repeat('a', Note::MAX_BODY_LENGTH + 1);

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', $longBody)
            ->call('createNote')
            ->assertHasErrors(['newNoteBody' => 'max']);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_note_body_at_max_length_is_valid(): void
    {
        $maxBody = str_repeat('a', Note::MAX_BODY_LENGTH);

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', $maxBody)
            ->call('createNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
        ]);
    }

    public function test_html_tags_are_stripped_from_note(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', '<script>alert("xss")</script><p>Safe content</p>')
            ->call('createNote')
            ->assertHasNoErrors();

        $note = Note::first();
        $this->assertEquals('alert("xss")Safe content', $note->body);
    }

    public function test_note_preserves_line_breaks(): void
    {
        $bodyWithLineBreaks = "Line 1\nLine 2\nLine 3";

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', $bodyWithLineBreaks)
            ->call('createNote')
            ->assertHasNoErrors();

        $note = Note::first();
        $this->assertStringContainsString("\n", $note->body);
        $this->assertEquals($bodyWithLineBreaks, $note->body);
    }

    public function test_whitespace_only_note_is_rejected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', '   ')
            ->call('createNote')
            ->assertHasErrors(['newNoteBody']);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_note_body_is_trimmed(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', '  Content with spaces  ')
            ->call('createNote')
            ->assertHasNoErrors();

        $note = Note::first();
        $this->assertEquals('Content with spaces', $note->body);
    }

    public function test_success_message_is_shown_after_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'Test note')
            ->call('createNote')
            ->assertSet('showSuccessMessage', true)
            ->assertSee('Nota guardada correctamente');
    }

    public function test_form_is_cleared_after_successful_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->set('newNoteBody', 'Test note')
            ->call('createNote')
            ->assertSet('newNoteBody', '');
    }
}
