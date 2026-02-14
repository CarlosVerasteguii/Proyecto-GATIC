<?php

namespace Tests\Feature\Movements;

use App\Actions\Inventory\Adjustments\ApplyAssetAdjustment;
use App\Actions\Inventory\Adjustments\ApplyProductQuantityAdjustment;
use App\Actions\Movements\Assets\AssignAssetToEmployee;
use App\Actions\Movements\Assets\LoanAssetToEmployee;
use App\Actions\Movements\Assets\ReturnLoanedAsset;
use App\Actions\Movements\Assets\UnassignAssetFromEmployee;
use App\Actions\Movements\Products\RegisterProductQuantityMovement;
use App\Actions\Movements\Undo\CreateUndoToken;
use App\Actions\Movements\Undo\UndoMovementByToken;
use App\Enums\UserRole;
use App\Livewire\Movements\UndoManager;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\UndoToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class UndoMovementByTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['gatic.inventory.undo.window_s' => 3600]);
    }

    private function createSerializedProductWithAsset(string $status = Asset::STATUS_AVAILABLE): array
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-001',
            'asset_tag' => null,
            'status' => $status,
        ]);

        return compact('location', 'category', 'product', 'asset');
    }

    private function createQuantityProduct(int $qtyTotal = 100): array
    {
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Toner HP',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => $qtyTotal,
        ]);

        return compact('category', 'product');
    }

    public function test_lector_cannot_undo_via_undo_manager(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        Livewire::actingAs($lector)
            ->test(UndoManager::class)
            ->call('undo', (string) Str::uuid())
            ->assertForbidden();
    }

    public function test_undo_is_restricted_to_actor_but_admin_can_override(): void
    {
        $editor1 = User::factory()->create(['role' => UserRole::Editor]);
        $editor2 = User::factory()->create(['role' => UserRole::Editor]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $movement = (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor1->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor1->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor2->id,
            ]);
            $this->fail('Expected ValidationException for non-actor user.');
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['Solo el usuario que realizó el movimiento puede deshacerlo.'],
            ], $e->errors());
        }

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $admin->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);
    }

    public function test_expired_token_cannot_be_used(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $movement = (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $token->expires_at = now()->subSecond();
        $token->save();

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['La ventana para deshacer ya expiró.'],
            ], $e->errors());

            throw $e;
        }
    }

    public function test_double_undo_is_idempotent(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $movement = (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $first = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $first['type']);
        $this->assertDatabaseCount('asset_movements', 2);

        $second = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('info', $second['type']);
        $this->assertDatabaseCount('asset_movements', 2);
    }

    public function test_cannot_undo_when_there_are_subsequent_movements(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $assign = (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor->id,
        ]);

        (new UnassignAssetFromEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Desasignación posterior',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $assign->id,
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['No se puede deshacer porque existen movimientos posteriores para este activo.'],
            ], $e->errors());

            $this->assertDatabaseHas('assets', [
                'id' => $asset->id,
                'status' => Asset::STATUS_AVAILABLE,
                'current_employee_id' => null,
            ]);

            $this->assertDatabaseCount('asset_movements', 2);

            throw $e;
        }
    }

    public function test_inventory_adjustment_after_movement_blocks_undo_for_asset(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['location' => $location, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $baseNow = now();

        $this->travelTo($baseNow->copy()->subMinutes(10));
        $movement = (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor->id,
        ]);
        $this->travelBack();

        (new ApplyAssetAdjustment)->execute([
            'asset_id' => $asset->id,
            'new_status' => Asset::STATUS_ASSIGNED, // keep state the same
            'new_location_id' => $location->id, // keep state the same
            'reason' => 'Ajuste posterior',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['No se puede deshacer porque existe un ajuste de inventario posterior para este activo.'],
            ], $e->errors());

            throw $e;
        }
    }

    public function test_undo_unassign_restores_assignment(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        (new AssignAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación inicial',
            'actor_user_id' => $editor->id,
        ]);

        $unassignMovement = (new UnassignAssetFromEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Desasignación',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $unassignMovement->id,
        ]);

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'type' => AssetMovement::TYPE_ASSIGN,
            'employee_id' => $employee->id,
            'actor_user_id' => $editor->id,
        ]);
    }

    public function test_undo_loan_creates_return_and_captures_due_date(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $dueDate = today()->addDays(7)->format('Y-m-d');

        $loanMovement = (new LoanAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Préstamo inicial',
            'loan_due_date' => $dueDate,
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $loanMovement->id,
        ]);

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
            'loan_due_date' => null,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'type' => AssetMovement::TYPE_RETURN,
            'loan_due_date' => $dueDate,
        ]);
    }

    public function test_undo_return_restores_loan_due_date(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $dueDate = today()->addDays(7)->format('Y-m-d');

        (new LoanAssetToEmployee)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Préstamo inicial',
            'loan_due_date' => $dueDate,
            'actor_user_id' => $editor->id,
        ]);

        $returnMovement = (new ReturnLoanedAsset)->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Devolución',
            'actor_user_id' => $editor->id,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'id' => $returnMovement->id,
            'type' => AssetMovement::TYPE_RETURN,
            'loan_due_date' => $dueDate,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_ASSET_MOVEMENT,
            'movement_id' => $returnMovement->id,
        ]);

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_LOANED,
            'current_employee_id' => $employee->id,
            'loan_due_date' => $dueDate,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'type' => AssetMovement::TYPE_LOAN,
            'loan_due_date' => $dueDate,
        ]);
    }

    public function test_undo_product_qty_movement_reverts_stock_and_creates_compensating_movement(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $movement = (new RegisterProductQuantityMovement)->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 10,
            'note' => 'Salida inicial',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_PRODUCT_QTY_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 100,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 10,
            'qty_before' => 90,
            'qty_after' => 100,
        ]);
    }

    public function test_undo_incoming_product_qty_movement_creates_outgoing_compensating_movement(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product] = $this->createQuantityProduct(50);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $movement = (new RegisterProductQuantityMovement)->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 20,
            'note' => 'Entrada inicial',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_PRODUCT_QTY_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $result = (new UndoMovementByToken)->execute([
            'token_id' => $token->id,
            'actor_user_id' => $editor->id,
        ]);

        $this->assertSame('success', $result['type']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 50,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 20,
            'qty_before' => 70,
            'qty_after' => 50,
        ]);
    }

    public function test_product_qty_cannot_undo_non_last_movement(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $first = (new RegisterProductQuantityMovement)->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 5,
            'note' => 'Salida 1',
            'actor_user_id' => $editor->id,
        ]);

        (new RegisterProductQuantityMovement)->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 2,
            'note' => 'Entrada 2',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_PRODUCT_QTY_MOVEMENT,
            'movement_id' => $first->id,
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['No se puede deshacer porque existen movimientos posteriores para este producto.'],
            ], $e->errors());

            throw $e;
        }
    }

    public function test_inventory_adjustment_after_movement_blocks_undo_for_product_qty(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create(['rpe' => 'EMP001', 'name' => 'Juan Pérez']);

        $baseNow = now();

        $this->travelTo($baseNow->copy()->subMinutes(10));
        $movement = ProductQuantityMovement::query()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $editor->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 5,
            'qty_before' => 100,
            'qty_after' => 95,
            'note' => 'Salida inicial',
        ]);
        $product->qty_total = 95;
        $product->save();
        $this->travelBack();

        (new ApplyProductQuantityAdjustment)->execute([
            'product_id' => $product->id,
            'new_qty' => 95, // keep state the same
            'reason' => 'Ajuste posterior',
            'actor_user_id' => $editor->id,
        ]);

        $token = (new CreateUndoToken)->execute([
            'actor_user_id' => $editor->id,
            'movement_kind' => UndoToken::KIND_PRODUCT_QTY_MOVEMENT,
            'movement_id' => $movement->id,
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new UndoMovementByToken)->execute([
                'token_id' => $token->id,
                'actor_user_id' => $editor->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertSame([
                'token' => ['No se puede deshacer porque existe un ajuste de inventario posterior para este producto.'],
            ], $e->errors());

            throw $e;
        }
    }
}
