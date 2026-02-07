<?php

namespace Tests\Feature\Timeline;

use App\Enums\UserRole;
use App\Livewire\Ui\TimelinePanel;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Support\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for timeline RBAC (AC3).
 *
 * - Lector can view timeline on Product/Asset but NOT attachment events
 * - Admin/Editor can view timeline with attachment events
 * - Lector cannot see attachment metadata (names, IDs) in timeline
 */
class TimelineRbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    private Product $product;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Lector can view timeline on Product (without attachments)
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_can_view_timeline_on_product(): void
    {
        Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk()
            ->assertSee('Timeline');
    }

    public function test_lector_can_view_timeline_on_asset(): void
    {
        Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Asset::class,
                'entityId' => $this->asset->id,
            ])
            ->assertOk();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Lector does NOT see attachment events in timeline
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_does_not_see_attachment_events_in_product_timeline(): void
    {
        // Create an attachment on the product
        Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'secret-document.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/secret-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);

        // Also add an audit log for attachment delete
        AuditRecorder::recordSync(
            action: AuditLog::ACTION_ATTACHMENT_DELETE,
            subjectType: Product::class,
            subjectId: $this->product->id,
            actorUserId: $this->admin->id,
            context: ['summary' => 'deleted-secret.pdf']
        );

        $component = Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk();

        // The attachment name should NOT appear in the rendered HTML
        $component->assertDontSee('secret-document.pdf');
        $component->assertDontSee('deleted-secret.pdf');
        $component->assertDontSee('Adjunto subido');
        $component->assertDontSee('Adjunto eliminado');
    }

    public function test_lector_does_not_see_attachment_metadata_in_asset_timeline(): void
    {
        Attachment::create([
            'attachable_type' => Asset::class,
            'attachable_id' => $this->asset->id,
            'uploaded_by_user_id' => $this->editor->id,
            'original_name' => 'confidential-report.xlsx',
            'disk' => 'local',
            'path' => 'attachments/Asset/1/conf-uuid',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 5000,
        ]);

        $component = Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Asset::class,
                'entityId' => $this->asset->id,
            ])
            ->assertOk();

        $component->assertDontSee('confidential-report.xlsx');
        $component->assertDontSee('attachments.download');
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Lector does NOT see "Adjuntos" filter chip
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_does_not_see_adjuntos_filter(): void
    {
        Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk()
            ->assertDontSee('Adjuntos');
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Admin/Editor CAN see attachment events
    // ───────────────────────────────────────────────────────────────────────

    public function test_admin_sees_attachment_events_in_product_timeline(): void
    {
        Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->admin->id,
            'original_name' => 'visible-report.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/visible-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk()
            ->assertSee('visible-report.pdf')
            ->assertSee('Adjunto subido');
    }

    public function test_editor_sees_attachment_events_in_timeline(): void
    {
        Attachment::create([
            'attachable_type' => Product::class,
            'attachable_id' => $this->product->id,
            'uploaded_by_user_id' => $this->editor->id,
            'original_name' => 'editor-file.pdf',
            'disk' => 'local',
            'path' => 'attachments/Product/1/editor-uuid',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1500,
        ]);

        Livewire::actingAs($this->editor)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => $this->product->id,
            ])
            ->assertOk()
            ->assertSee('editor-file.pdf');
    }

    // ───────────────────────────────────────────────────────────────────────
    // Security: invalid entity type returns 404
    // ───────────────────────────────────────────────────────────────────────

    public function test_invalid_entity_type_returns_404(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => User::class,
                'entityId' => 1,
            ])
            ->assertNotFound();
    }

    public function test_invalid_entity_id_returns_404(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TimelinePanel::class, [
                'entityType' => Product::class,
                'entityId' => 0,
            ])
            ->assertNotFound();
    }

    // ───────────────────────────────────────────────────────────────────────
    // AC3: Lector cannot access Employee timeline (requires inventory.manage)
    // ───────────────────────────────────────────────────────────────────────

    public function test_lector_cannot_view_employee_timeline(): void
    {
        $employee = Employee::factory()->create();

        Livewire::actingAs($this->lector)
            ->test(TimelinePanel::class, [
                'entityType' => Employee::class,
                'entityId' => $employee->id,
            ])
            ->assertForbidden();
    }
}
