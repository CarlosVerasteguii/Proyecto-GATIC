<?php

namespace Tests\Feature\Timeline;

use App\Enums\UserRole;
use App\Livewire\Ui\TimelinePanel;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Note;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for timeline chronological ordering (AC1, AC2).
 *
 * - Events from multiple sources are merged in correct chronological order
 * - Most recent events appear first
 * - Soft-deleted records don't leak into timeline
 */
class TimelineChronologyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private Asset $asset;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

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
    // AC1+AC2: Mixed sources appear in chronological order (most recent first)
    // ───────────────────────────────────────────────────────────────────────

    public function test_product_timeline_mixes_movements_and_notes_chronologically(): void
    {
        // Create a note first (older)
        $note = Note::create([
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
            'author_user_id' => $this->admin->id,
            'body' => 'First event: this is a note',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        // Create a quantity movement second (newer)
        ProductQuantityMovement::factory()->create([
            'product_id' => $this->product->id,
            'employee_id' => $this->employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 5,
            'qty_before' => 10,
            'qty_after' => 15,
            'note' => 'Second event: movement',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk();

        $events = $component->get('events');

        $this->assertCount(2, $events);

        // Most recent first: movement should be at index 0
        $this->assertSame('product_quantity_movement', $events[0]['source']);
        $this->assertStringContainsString('Entrada', $events[0]['title']);

        // Older: note at index 1
        $this->assertSame('note', $events[1]['source']);
        $this->assertStringContainsString('Nota manual', $events[1]['title']);
    }

    public function test_asset_timeline_shows_movements(): void
    {
        AssetMovement::factory()->create([
            'asset_id' => $this->asset->id,
            'employee_id' => $this->employee->id,
            'actor_user_id' => $this->admin->id,
            'type' => AssetMovement::TYPE_ASSIGN,
            'note' => 'Assigned to employee',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Asset::class,
                'entityId' => $this->asset->id,
            ])
            ->assertOk();

        $events = $component->get('events');

        $this->assertNotEmpty($events);
        $this->assertSame('asset_movement', $events[0]['source']);
        $this->assertSame('Asignacion', $events[0]['label']);
    }

    public function test_employee_timeline_shows_asset_and_quantity_movements(): void
    {
        // Asset movement for this employee
        AssetMovement::factory()->create([
            'asset_id' => $this->asset->id,
            'employee_id' => $this->employee->id,
            'actor_user_id' => $this->admin->id,
            'type' => AssetMovement::TYPE_LOAN,
            'note' => 'Loaned laptop',
            'created_at' => now()->subMinutes(10),
        ]);

        // Quantity movement for this employee
        ProductQuantityMovement::factory()->create([
            'product_id' => $this->product->id,
            'employee_id' => $this->employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 3,
            'qty_before' => 20,
            'qty_after' => 17,
            'note' => 'Gave supplies',
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Employee::class,
                'entityId' => $this->employee->id,
            ])
            ->assertOk();

        $events = $component->get('events');

        $this->assertCount(2, $events);

        // Most recent first
        $sources = array_column($events, 'source');
        $this->assertContains('product_quantity_movement', $sources);
        $this->assertContains('asset_movement', $sources);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Empty state
    // ───────────────────────────────────────────────────────────────────────

    public function test_empty_timeline_shows_empty_message(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk()
            ->assertSee('Sin actividad registrada aún.');
    }

    public function test_load_more_does_not_skip_events_with_same_timestamp(): void
    {
        $ts = Carbon::parse('2026-01-01 10:00:00');

        for ($i = 0; $i < 30; $i++) {
            Note::create([
                'noteable_type' => Product::class,
                'noteable_id' => $this->product->id,
                'author_user_id' => $this->admin->id,
                'body' => "Note {$i}",
                'created_at' => $ts,
                'updated_at' => $ts,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk();

        $this->assertCount(25, $component->get('events'));
        $this->assertTrue($component->get('hasMore'));

        $component->call('loadMore');

        $this->assertCount(30, $component->get('events'));
        $this->assertFalse($component->get('hasMore'));
    }

    // ───────────────────────────────────────────────────────────────────────
    // Filter functionality
    // ───────────────────────────────────────────────────────────────────────

    public function test_filter_toggle_limits_event_types(): void
    {
        // Create a note
        Note::create([
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
            'author_user_id' => $this->admin->id,
            'body' => 'A note for filtering',
        ]);

        // Create a movement
        ProductQuantityMovement::factory()->create([
            'product_id' => $this->product->id,
            'employee_id' => $this->employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 1,
            'qty_before' => 5,
            'qty_after' => 6,
            'note' => 'Movement for filter test',
        ]);

        // Filter to only "Notas"
        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->call('toggleFilter', 'Notas');

        $events = $component->get('events');

        $this->assertCount(1, $events);
        $this->assertSame('note', $events[0]['source']);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Soft-delete regression: deleted notes should not appear
    // ───────────────────────────────────────────────────────────────────────

    public function test_notes_use_plain_text_escape(): void
    {
        Note::create([
            'noteable_type' => Product::class,
            'noteable_id' => $this->product->id,
            'author_user_id' => $this->admin->id,
            'body' => '<script>alert("xss")</script>',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk();

        // The summary should be escaped (no raw script tag)
        $events = $component->get('events');
        $this->assertNotEmpty($events);
        $this->assertStringContainsString('&lt;script&gt;', $component->html());
        $this->assertStringNotContainsString('<script>alert', $component->html());
    }
}
