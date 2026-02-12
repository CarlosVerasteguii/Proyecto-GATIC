<?php

namespace Tests\Feature\Movements\Assets;

use App\Enums\UserRole;
use App\Livewire\Inventory\Assets\AssetsGlobalIndex;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BulkAssignAssetsToEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_assign_assets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $assets = Asset::factory()->count(3)->create([
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsGlobalIndex::class)
            ->set('selectedAssetIds', $assets->pluck('id')->all())
            ->set('bulkEmployeeId', $employee->id)
            ->set('bulkNote', 'Asignación masiva de prueba')
            ->call('bulkAssign')
            ->assertHasNoErrors()
            ->assertSet('selectedAssetIds', [])
            ->assertSet('showBulkAssignModal', false);

        foreach ($assets as $asset) {
            $this->assertDatabaseHas('assets', [
                'id' => $asset->id,
                'status' => Asset::STATUS_ASSIGNED,
                'current_employee_id' => $employee->id,
            ]);

            $this->assertDatabaseHas('asset_movements', [
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'actor_user_id' => $admin->id,
                'type' => AssetMovement::TYPE_ASSIGN,
                'note' => 'Asignación masiva de prueba',
            ]);
        }
    }

    public function test_bulk_assign_is_all_or_nothing_when_one_asset_is_not_assignable(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $previousEmployee = Employee::factory()->create();

        $assignable = Asset::factory()->create([
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        $notAssignable = Asset::factory()->create([
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $previousEmployee->id,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsGlobalIndex::class)
            ->set('selectedAssetIds', [$assignable->id, $notAssignable->id])
            ->set('bulkEmployeeId', $employee->id)
            ->set('bulkNote', 'Asignación masiva de prueba')
            ->call('bulkAssign')
            ->assertHasErrors(['selectedAssetIds']);

        $this->assertDatabaseCount('asset_movements', 0);

        $this->assertDatabaseHas('assets', [
            'id' => $assignable->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $notAssignable->id,
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $previousEmployee->id,
        ]);
    }

    public function test_bulk_assign_respects_max_assets_limit(): void
    {
        config()->set('gatic.inventory.bulk_actions.max_assets', 1);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $assets = Asset::factory()->count(2)->create([
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsGlobalIndex::class)
            ->set('selectedAssetIds', $assets->pluck('id')->all())
            ->set('bulkEmployeeId', $employee->id)
            ->set('bulkNote', 'Asignación masiva de prueba')
            ->call('bulkAssign')
            ->assertHasErrors(['selectedAssetIds']);

        $this->assertDatabaseCount('asset_movements', 0);
    }

    public function test_lector_cannot_see_or_execute_bulk_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        Asset::factory()->create();

        $component = Livewire::actingAs($lector)
            ->test(AssetsGlobalIndex::class)
            ->set('selectedAssetIds', [1])
            ->set('showBulkAssignModal', true)
            ->assertDontSee('data-testid="assets-row-checkbox"')
            ->assertDontSee('data-testid="assets-bulk-bar"')
            ->assertDontSee('data-testid="assets-bulk-assign-modal"');

        $component->call('openBulkAssignModal')
            ->assertForbidden();
    }
}
