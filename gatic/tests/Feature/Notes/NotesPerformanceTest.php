<?php

namespace Tests\Feature\Notes;

use App\Enums\UserRole;
use App\Livewire\Ui\NotesPanel;
use App\Models\Category;
use App\Models\Note;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for notes performance requirements (AC6).
 *
 * - Notes are paginated (20 per page)
 * - No N+1 for author (eager load)
 */
class NotesPerformanceTest extends TestCase
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

    public function test_notes_are_paginated(): void
    {
        // Create 25 notes (more than one page of 20)
        for ($i = 0; $i < 25; $i++) {
            $this->product->notes()->create([
                'author_user_id' => $this->admin->id,
                'body' => "Note {$i}",
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        // Should show 20 notes on first page
        $notes = $component->get('notes');
        $this->assertCount(20, $notes->items());
        $this->assertEquals(25, $notes->total());
        $this->assertEquals(2, $notes->lastPage());
    }

    public function test_author_is_eager_loaded(): void
    {
        // Create multiple users and notes
        $users = User::factory()->count(5)->create(['role' => UserRole::Editor]);
        foreach ($users as $user) {
            $this->product->notes()->create([
                'author_user_id' => $user->id,
                'body' => "Note from {$user->name}",
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, Note> $notes */
        $notes = $component->get('notes');

        foreach ($notes->items() as $note) {
            $this->assertTrue($note->relationLoaded('author'));
            $this->assertNotNull($note->author);
        }
    }

    public function test_notes_are_ordered_newest_first(): void
    {
        $baseNow = Carbon::create(2026, 1, 1, 12, 0, 0, 'UTC');

        Carbon::setTestNow($baseNow->copy()->subDay());
        $oldNote = $this->product->notes()->create([
            'author_user_id' => $this->admin->id,
            'body' => 'Old note',
        ]);

        Carbon::setTestNow($baseNow);
        $newNote = $this->product->notes()->create([
            'author_user_id' => $this->admin->id,
            'body' => 'New note',
        ]);
        Carbon::setTestNow();

        $component = Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ]);

        $notes = $component->get('notes');
        $this->assertEquals($newNote->id, $notes->items()[0]->id);
        $this->assertEquals($oldNote->id, $notes->items()[1]->id);
    }

    public function test_empty_notes_shows_placeholder(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->assertSee('Sin notas');
    }

    public function test_note_count_is_displayed_in_header(): void
    {
        $this->product->notes()->create([
            'author_user_id' => $this->admin->id,
            'body' => 'Test note',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotesPanel::class, [
                'noteableType' => Product::class,
                'noteableId' => $this->product->id,
            ])
            ->assertSee('Notas')
            ->assertSee('1'); // Badge with count
    }
}
