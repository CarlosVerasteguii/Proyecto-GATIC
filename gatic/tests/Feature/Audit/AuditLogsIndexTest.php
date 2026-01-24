<?php

namespace Tests\Feature\Audit;

use App\Livewire\Admin\Audit\AuditLogsIndex;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for AuditLogsIndex Livewire component (AC3).
 *
 * - Paginated list ordered by most recent
 * - Filters: date range, actor, action, subject type
 * - Detail view
 */
class AuditLogsIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
        $this->editor = User::factory()->create(['role' => 'Editor', 'name' => 'Editor User']);
    }

    public function test_admin_sees_paginated_audit_logs(): void
    {
        // Create 25 audit logs to test pagination
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'action' => AuditLog::ACTION_ASSET_ASSIGN,
                'subject_type' => 'App\\Models\\AssetMovement',
                'subject_id' => $i + 1,
                'actor_user_id' => $this->editor->id,
                'context' => ['index' => $i],
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $newestLog = AuditLog::query()->orderByDesc('created_at')->firstOrFail();
        $oldestLog = AuditLog::query()->orderBy('created_at')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSee('Registro de Auditoría')
            // Ensure rows are rendered (avoid false positives from filter dropdown labels).
            ->assertSeeHtml('wire:click="showDetail('.$newestLog->id.')"')
            // Page 1 is 20 items; the oldest log should be on page 2.
            ->assertDontSeeHtml('wire:click="showDetail('.$oldestLog->id.')"');
    }

    public function test_logs_are_ordered_by_most_recent(): void
    {
        $oldLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now()->subHours(2),
        ]);

        $newLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_RETURN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 2,
            'actor_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSeeHtmlInOrder([
                'wire:click="showDetail('.$newLog->id.')"',
                'wire:click="showDetail('.$oldLog->id.')"',
            ]);
    }

    public function test_filter_by_date_range(): void
    {
        $oldLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now()->subDays(10),
        ]);

        $recentLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_RETURN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 2,
            'actor_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        // Filter shows only recent log (return), not old log (loan)
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
            ->assertSeeHtml('wire:click="showDetail('.$recentLog->id.')"')
            ->assertDontSeeHtml('wire:click="showDetail('.$oldLog->id.')"');
    }

    public function test_filter_by_actor(): void
    {
        $editorLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now(),
        ]);

        $adminLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_RETURN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 2,
            'actor_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        // Filter by editor shows only editor's log
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->set('actorId', $this->editor->id)
            ->assertSeeHtml('wire:click="showDetail('.$editorLog->id.')"')
            ->assertDontSeeHtml('wire:click="showDetail('.$adminLog->id.')"');
    }

    public function test_filter_by_action(): void
    {
        $loanLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now(),
        ]);

        $returnLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_RETURN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 2,
            'actor_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        // Filter by loan action shows only loan log
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->set('action', AuditLog::ACTION_ASSET_LOAN)
            ->assertSeeHtml('wire:click="showDetail('.$loanLog->id.')"')
            ->assertDontSeeHtml('wire:click="showDetail('.$returnLog->id.')"');
    }

    public function test_filter_by_subject_type(): void
    {
        $assetLog = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now(),
        ]);

        $adjustmentLog = AuditLog::create([
            'action' => AuditLog::ACTION_INVENTORY_ADJUSTMENT,
            'subject_type' => 'App\\Models\\InventoryAdjustmentEntry',
            'subject_id' => 2,
            'actor_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->set('subjectType', 'App\\Models\\InventoryAdjustmentEntry')
            ->assertSee('Ajuste de inventario')
            ->assertSee('InventoryAdjustmentEntry')
            ->assertSeeHtml('wire:click="showDetail('.$adjustmentLog->id.')"')
            ->assertDontSeeHtml('wire:click="showDetail('.$assetLog->id.')"');
    }

    public function test_clear_filters_resets_all(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->set('dateFrom', '2025-01-01')
            ->set('dateTo', '2025-12-31')
            ->set('actorId', $this->editor->id)
            ->set('action', AuditLog::ACTION_ASSET_LOAN)
            ->set('subjectType', 'App\\Models\\AssetMovement')
            ->call('clearFilters')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('actorId', null)
            ->assertSet('action', '')
            ->assertSet('subjectType', '');
    }

    public function test_show_detail_opens_modal(): void
    {
        $log = AuditLog::create([
            'action' => AuditLog::ACTION_LOCK_FORCE_RELEASE,
            'subject_type' => 'App\\Models\\PendingTask',
            'subject_id' => 123,
            'actor_user_id' => $this->admin->id,
            'context' => ['previous_locked_by' => 5, 'test_key' => 'test_value'],
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->call('showDetail', $log->id)
            ->assertSet('selectedLogId', $log->id)
            ->assertSee('Detalle de Auditoría')
            ->assertSee('Lock liberado (admin)')
            ->assertSee('PendingTask')
            ->assertSee('previous_locked_by')
            ->assertSee('test_value');
    }

    public function test_close_detail_closes_modal(): void
    {
        $log = AuditLog::create([
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
            'actor_user_id' => $this->editor->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->call('showDetail', $log->id)
            ->assertSet('selectedLogId', $log->id)
            ->call('closeDetail')
            ->assertSet('selectedLogId', null);
    }

    public function test_empty_state_shows_message(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSee('No hay registros de auditoría');
    }

    public function test_has_active_filters_returns_correct_state(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class);

        // No filters
        $this->assertFalse($component->instance()->hasActiveFilters());

        // With date filter
        $component->set('dateFrom', '2025-01-01');
        $this->assertTrue($component->instance()->hasActiveFilters());

        // Clear and test another filter
        $component->call('clearFilters');
        $component->set('actorId', $this->editor->id);
        $this->assertTrue($component->instance()->hasActiveFilters());
    }
}
