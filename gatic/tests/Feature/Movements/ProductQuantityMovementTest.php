<?php

namespace Tests\Feature\Movements;

use App\Actions\Movements\Products\RegisterProductQuantityMovement;
use App\Enums\UserRole;
use App\Livewire\Movements\Products\QuantityMovementForm;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductQuantityMovementTest extends TestCase
{
    use RefreshDatabase;

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

    private function createSerializedProduct(): array
    {
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

        return compact('category', 'product');
    }

    public function test_admin_can_access_quantity_movement_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/movements/quantity")
            ->assertOk();
    }

    public function test_editor_can_access_quantity_movement_route(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product] = $this->createQuantityProduct();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/movements/quantity")
            ->assertOk();
    }

    public function test_lector_cannot_access_quantity_movement_route(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product] = $this->createQuantityProduct();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/movements/quantity")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_register_livewire_action(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product] = $this->createQuantityProduct();

        Livewire::actingAs($lector)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->assertForbidden();
    }

    public function test_route_returns_404_for_serialized_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createSerializedProduct();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/movements/quantity")
            ->assertNotFound();
    }

    public function test_admin_can_register_outgoing_movement(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
            'department' => 'IT',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', 'Entrega para proyecto X')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.show', ['product' => $product->id]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 90,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $admin->id,
            'direction' => 'out',
            'qty' => 10,
            'qty_before' => 100,
            'qty_after' => 90,
        ]);
    }

    public function test_admin_can_register_incoming_movement(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(50);
        $employee = Employee::query()->create([
            'rpe' => 'EMP002',
            'name' => 'Maria Lopez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'in')
            ->set('qty', 20)
            ->set('employeeId', $employee->id)
            ->set('note', 'Devolucion de proyecto terminado')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.show', ['product' => $product->id]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 70,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => 'in',
            'qty' => 20,
            'qty_before' => 50,
            'qty_after' => 70,
        ]);
    }

    public function test_note_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', '')
            ->call('register')
            ->assertHasErrors(['note']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 100,
        ]);

        $this->assertDatabaseMissing('product_quantity_movements', [
            'product_id' => $product->id,
        ]);
    }

    public function test_note_is_required_for_incoming_movement(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'in')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', '')
            ->call('register')
            ->assertHasErrors(['note']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 100,
        ]);

        $this->assertDatabaseMissing('product_quantity_movements', [
            'product_id' => $product->id,
        ]);
    }

    public function test_note_must_be_at_least_5_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', 'abc')
            ->call('register')
            ->assertHasErrors(['note']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 100,
        ]);
    }

    public function test_employee_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', null)
            ->set('note', 'Nota valida de prueba')
            ->call('register')
            ->assertHasErrors(['employeeId']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 100,
        ]);
    }

    public function test_quantity_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', null)
            ->set('employeeId', $employee->id)
            ->set('note', 'Nota valida de prueba')
            ->call('register')
            ->assertHasErrors(['qty']);
    }

    public function test_quantity_must_be_at_least_1(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 0)
            ->set('employeeId', $employee->id)
            ->set('note', 'Nota valida de prueba')
            ->call('register')
            ->assertHasErrors(['qty']);
    }

    public function test_cannot_register_outgoing_movement_when_insufficient_stock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(5);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', 'Intento de salida excesiva')
            ->call('register')
            ->assertHasErrors(['qty']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 5,
        ]);

        $this->assertDatabaseMissing('product_quantity_movements', [
            'product_id' => $product->id,
        ]);
    }

    public function test_action_rejects_serialized_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createSerializedProduct();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new RegisterProductQuantityMovement;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => 'out',
            'qty' => 1,
            'note' => 'Intento en producto serializado',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_action_creates_movement_record_with_correct_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(100);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new RegisterProductQuantityMovement;
        $movement = $action->execute([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'direction' => 'out',
            'qty' => 25,
            'note' => 'Movimiento de prueba completo',
            'actor_user_id' => $admin->id,
        ]);

        $this->assertInstanceOf(ProductQuantityMovement::class, $movement);
        $this->assertEquals($product->id, $movement->product_id);
        $this->assertEquals($employee->id, $movement->employee_id);
        $this->assertEquals($admin->id, $movement->actor_user_id);
        $this->assertEquals('out', $movement->direction);
        $this->assertEquals(25, $movement->qty);
        $this->assertEquals(100, $movement->qty_before);
        $this->assertEquals(75, $movement->qty_after);
        $this->assertEquals('Movimiento de prueba completo', $movement->note);
    }

    public function test_product_show_displays_movement_button_for_quantity_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk()
            ->assertSee('Registrar movimiento');
    }

    public function test_product_show_does_not_display_movement_button_for_serialized_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createSerializedProduct();

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}");

        $response->assertOk();
        $response->assertDontSee('Registrar movimiento');
    }

    public function test_product_show_does_not_display_movement_button_for_lector(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product] = $this->createQuantityProduct();

        $response = $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}");

        $response->assertOk();
        $response->assertDontSee('Registrar movimiento');
    }

    public function test_outgoing_movement_can_reduce_stock_to_zero(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(10);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'out')
            ->set('qty', 10)
            ->set('employeeId', $employee->id)
            ->set('note', 'Agotar stock completamente')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.show', ['product' => $product->id]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 0,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'qty_before' => 10,
            'qty_after' => 0,
        ]);
    }

    public function test_incoming_movement_works_from_zero_stock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product] = $this->createQuantityProduct(0);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(QuantityMovementForm::class, ['product' => (string) $product->id])
            ->set('direction', 'in')
            ->set('qty', 50)
            ->set('employeeId', $employee->id)
            ->set('note', 'Reabastecimiento inicial')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.show', ['product' => $product->id]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'qty_total' => 50,
        ]);

        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $product->id,
            'qty_before' => 0,
            'qty_after' => 50,
        ]);
    }
}
