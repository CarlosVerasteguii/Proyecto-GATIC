<?php

namespace Tests\Feature\Notes;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Note;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Note model and relationships (AC1).
 *
 * - Note is associated with noteable entity (morph)
 * - Note has author (user)
 * - Noteable entities have notes() relation
 */
class NoteModelTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => UserRole::Editor]);
    }

    public function test_note_belongs_to_author(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $note = $product->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Test note',
        ]);

        $this->assertInstanceOf(User::class, $note->author);
        $this->assertEquals($this->author->id, $note->author->id);
    }

    public function test_note_morphs_to_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $note = $product->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Product note',
        ]);

        $this->assertInstanceOf(Product::class, $note->noteable);
        $this->assertEquals($product->id, $note->noteable->id);
    }

    public function test_note_morphs_to_asset(): void
    {
        $category = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        $note = $asset->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Asset note',
        ]);

        $this->assertInstanceOf(Asset::class, $note->noteable);
        $this->assertEquals($asset->id, $note->noteable->id);
    }

    public function test_note_morphs_to_employee(): void
    {
        $employee = Employee::factory()->create();

        $note = $employee->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Employee note',
        ]);

        $this->assertInstanceOf(Employee::class, $note->noteable);
        $this->assertEquals($employee->id, $note->noteable->id);
    }

    public function test_product_has_notes_relation(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $product->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Note 1',
        ]);

        $product->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Note 2',
        ]);

        $this->assertCount(2, $product->notes);
        $this->assertInstanceOf(Note::class, $product->notes->first());
    }

    public function test_asset_has_notes_relation(): void
    {
        $category = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        $asset->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Asset note',
        ]);

        $this->assertCount(1, $asset->notes);
    }

    public function test_employee_has_notes_relation(): void
    {
        $employee = Employee::factory()->create();

        $employee->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Employee note',
        ]);

        $this->assertCount(1, $employee->notes);
    }

    public function test_note_timestamps_are_recorded(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $note = $product->notes()->create([
            'author_user_id' => $this->author->id,
            'body' => 'Test note',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $note->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $note->updated_at);
    }
}
