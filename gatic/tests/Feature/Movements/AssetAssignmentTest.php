<?php

namespace Tests\Feature\Movements;

use App\Actions\Movements\Assets\AssignAssetToEmployee;
use App\Enums\UserRole;
use App\Livewire\Movements\Assets\AssignAssetForm;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetAssignmentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_access_assign_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/assign")
            ->assertOk();
    }

    public function test_editor_can_access_assign_route(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/assign")
            ->assertOk();
    }

    public function test_lector_cannot_access_assign_route(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/assign")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_assign_livewire_action(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        Livewire::actingAs($lector)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertForbidden();
    }

    public function test_admin_can_assign_available_asset_to_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
            'department' => 'IT',
        ]);

        Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'Asignación para proyecto nuevo')
            ->call('assign')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]));

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
        ]);
    }

    public function test_note_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', '')
            ->call('assign')
            ->assertHasErrors(['note']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);
    }

    public function test_note_must_be_at_least_5_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'abc')
            ->call('assign')
            ->assertHasErrors(['note']);
    }

    public function test_employee_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', null)
            ->set('note', 'Nota válida de prueba')
            ->call('assign')
            ->assertHasErrors(['employeeId']);
    }

    public function test_cannot_assign_already_assigned_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new AssignAssetToEmployee;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Intento de reasignación',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_cannot_assign_loaned_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new AssignAssetToEmployee;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Intento de asignar activo prestado',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_cannot_assign_retired_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_RETIRED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new AssignAssetToEmployee;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Intento de asignar activo retirado',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_assign_route_redirects_if_asset_not_assignable(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/assign")
            ->assertRedirect(route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]));
    }

    public function test_action_creates_movement_record_with_correct_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new AssignAssetToEmployee;
        $movement = $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Asignación de prueba completa',
            'actor_user_id' => $admin->id,
        ]);

        $this->assertInstanceOf(AssetMovement::class, $movement);
        $this->assertEquals($asset->id, $movement->asset_id);
        $this->assertEquals($employee->id, $movement->employee_id);
        $this->assertEquals($admin->id, $movement->actor_user_id);
        $this->assertEquals(AssetMovement::TYPE_ASSIGN, $movement->type);
        $this->assertEquals('Asignación de prueba completa', $movement->note);
    }

    public function test_asset_show_displays_assign_button_for_available_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Asignar');
    }

    public function test_asset_show_does_not_display_assign_button_for_assigned_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}");

        $response->assertOk();
        $response->assertDontSee('bi-person-check');
    }

    public function test_asset_show_displays_current_employee_when_assigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $asset->update([
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('EMP001')
            ->assertSee('Juan Perez')
            ->assertSee('Asignado');
    }

    public function test_employee_show_displays_assigned_assets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $asset->update([
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('Activos asignados (1)')
            ->assertSee('Dell X1')
            ->assertSee('SER-001');
    }

    public function test_employee_error_clears_when_employee_is_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
            'department' => 'IT',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', null)
            ->set('note', 'Nota valida de prueba')
            ->call('assign')
            ->assertHasErrors(['employeeId']);

        // Al seleccionar un empleado, el error debe limpiarse
        $component->set('employeeId', $employee->id)
            ->assertHasNoErrors(['employeeId']);
    }

    public function test_note_error_clears_when_note_is_corrected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(AssignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'ab')
            ->call('assign')
            ->assertHasErrors(['note']);

        // Al corregir la nota, el error debe limpiarse
        $component->set('note', 'Nota valida corregida')
            ->assertHasNoErrors(['note']);
    }
}
