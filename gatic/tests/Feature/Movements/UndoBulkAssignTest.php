<?php

namespace Tests\Feature\Movements;

use App\Actions\Movements\Assets\BulkAssignAssetsToEmployee;
use App\Actions\Movements\Assets\UnassignAssetFromEmployee;
use App\Actions\Movements\Undo\CreateUndoToken;
use App\Actions\Movements\Undo\UndoMovementByToken;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\UndoToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UndoBulkAssignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['gatic.inventory.undo.window_s' => 3600]);
    }

    private function createSerializedProductWithAssets(int $count): array
    {
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $assets = [];
        for ($i = 1; $i <= $count; $i++) {
            $assets[] = Asset::query()->create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'serial' => sprintf('SER-%03d', $i),
                'asset_tag' => null,
                'status' => Asset::STATUS_AVAILABLE,
            ]);
        }

        return compact('location', 'category', 'product', 'assets');
    }

    public function test_bulk_assign_can_be_undone_all_or_nothing(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);
        ['assets' => $assets] = $this->createSerializedProductWithAssets(3);

        $assetIds = array_map(static fn (Asset $a): int => $a->id, $assets);

        $result = (new BulkAssignAssetsToEmployee)->execute([
            'asset_ids' => $assetIds,
            'employee_id' => $employee->id,
            'note' => 'Asignación masiva inicial',
            'actor_user_id' => $editor->id,
        ]);

        $batchUuid = (string) $result['batch_uuid'];

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'batch_uuid' => $batchUuid,
        ]);

        $undoResult = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $undoResult['type']);

        foreach ($assets as $asset) {
            $this->assertDatabaseHas('assets', [
                'id' => $asset->id,
                'status' => Asset::STATUS_AVAILABLE,
                'current_employee_id' => null,
            ]);

            $this->assertDatabaseHas('asset_movements', [
                'asset_id' => $asset->id,
                'type' => AssetMovement::TYPE_UNASSIGN,
                'batch_uuid' => $batchUuid,
                'actor_user_id' => $editor->id,
            ]);
        }
    }

    public function test_bulk_undo_fails_if_any_asset_changed_and_reverts_nothing(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);
        ['assets' => $assets] = $this->createSerializedProductWithAssets(3);

        $assetIds = array_map(static fn (Asset $a): int => $a->id, $assets);

        $result = (new BulkAssignAssetsToEmployee)->execute([
            'asset_ids' => $assetIds,
            'employee_id' => $employee->id,
            'note' => 'Asignación masiva inicial',
            'actor_user_id' => $editor->id,
        ]);

        $batchUuid = (string) $result['batch_uuid'];

        $changedAsset = $assets[0];

        (new UnassignAssetFromEmployee)->execute([
            'asset_id' => $changedAsset->id,
            'employee_id' => $employee->id,
            'note' => 'Cambio posterior',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'batch_uuid' => $batchUuid,
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['No se puede deshacer porque uno o más activos tienen movimientos posteriores.'],
            ], $e->errors());

            $this->assertNull($token->fresh()->used_at);

            $this->assertDatabaseHas('assets', [
                'id' => $changedAsset->id,
                'status' => Asset::STATUS_AVAILABLE,
                'current_employee_id' => null,
            ]);

            foreach (array_slice($assets, 1) as $asset) {
                $this->assertDatabaseHas('assets', [
                    'id' => $asset->id,
                    'status' => Asset::STATUS_ASSIGNED,
                    'current_employee_id' => $employee->id,
                ]);

                $this->assertDatabaseMissing('asset_movements', [
                    'asset_id' => $asset->id,
                    'type' => AssetMovement::TYPE_UNASSIGN,
                    'batch_uuid' => $batchUuid,
                ]);
            }

            throw $e;
        }
    }
}
