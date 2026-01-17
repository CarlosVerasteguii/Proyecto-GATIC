<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Products\ProductKardex;
use App\Models\Category;
use App\Models\Employee;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductKardexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    private Category $categoryQuantity;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $this->editor = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);
        $this->lector = User::factory()->create(['role' => UserRole::Lector, 'is_active' => true]);

        $this->categoryQuantity = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        $this->product = Product::query()->create([
            'name' => 'Toner HP',
            'category_id' => $this->categoryQuantity->id,
            'brand_id' => null,
            'qty_total' => 100,
        ]);
    }

    // =====================
    // AC3 - RBAC Tests
    // =====================

    public function test_admin_can_view_product_kardex(): void
    {
        $this->actingAs($this->admin)
            ->get(route('inventory.products.kardex', ['product' => $this->product->id]))
            ->assertStatus(200);
    }

    public function test_editor_can_view_product_kardex(): void
    {
        $this->actingAs($this->editor)
            ->get(route('inventory.products.kardex', ['product' => $this->product->id]))
            ->assertStatus(200);
    }

    public function test_lector_can_view_product_kardex(): void
    {
        $this->actingAs($this->lector)
            ->get(route('inventory.products.kardex', ['product' => $this->product->id]))
            ->assertStatus(200);
    }

    public function test_guest_cannot_view_product_kardex(): void
    {
        $this->get(route('inventory.products.kardex', ['product' => $this->product->id]))
            ->assertRedirect(route('login'));
    }

    // =====================
    // AC1 - Kardex cronológico
    // =====================

    public function test_kardex_shows_quantity_movements_chronologically(): void
    {
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Pérez',
            'department' => 'IT',
        ]);

        $baseNow = now();

        // Create movements with controlled timestamps
        $this->travelTo($baseNow->copy()->subDays(2));
        ProductQuantityMovement::query()->create([
            'product_id' => $this->product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 5,
            'qty_before' => 100,
            'qty_after' => 95,
            'note' => 'Primera salida',
        ]);

        $this->travelTo($baseNow->copy()->subDay());
        ProductQuantityMovement::query()->create([
            'product_id' => $this->product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 3,
            'qty_before' => 95,
            'qty_after' => 98,
            'note' => 'Segunda entrada',
        ]);

        $this->travelBack();

        Livewire::actingAs($this->admin)
            ->test(ProductKardex::class, ['product' => (string) $this->product->id])
            ->assertSeeInOrder(['Entrada', 'Salida']);
    }

    public function test_kardex_shows_movement_details(): void
    {
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Pérez',
            'department' => 'IT',
        ]);

        ProductQuantityMovement::query()->create([
            'product_id' => $this->product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $this->admin->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 5,
            'qty_before' => 100,
            'qty_after' => 95,
            'note' => 'Entrega prueba',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProductKardex::class, ['product' => (string) $this->product->id])
            ->assertSee('Salida')
            ->assertSee('5')
            ->assertSee($this->admin->name)
            ->assertSee('Juan Pérez')
            ->assertSee('Entrega prueba');
    }

    // =====================
    // AC2 - Ajustes incluidos
    // =====================

    public function test_kardex_includes_inventory_adjustments(): void
    {
        $adjustment = InventoryAdjustment::query()->create([
            'actor_user_id' => $this->admin->id,
            'reason' => 'Ajuste por inventario físico',
        ]);

        InventoryAdjustmentEntry::query()->create([
            'inventory_adjustment_id' => $adjustment->id,
            'subject_type' => Product::class,
            'subject_id' => $this->product->id,
            'product_id' => $this->product->id,
            'before' => ['qty_total' => 100],
            'after' => ['qty_total' => 95],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProductKardex::class, ['product' => (string) $this->product->id])
            ->assertSee('Ajuste')
            ->assertSee('Ajuste por inventario físico')
            ->assertSee($this->admin->name);
    }

    public function test_kardex_shows_both_movements_and_adjustments_chronologically(): void
    {
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Pérez',
            'department' => 'IT',
        ]);

        // Movement first (older)
        $baseNow = now();

        $this->travelTo($baseNow->copy()->subDays(3));
        ProductQuantityMovement::query()->create([
            'product_id' => $this->product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $this->editor->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 10,
            'qty_before' => 100,
            'qty_after' => 90,
            'note' => 'Salida antigua',
        ]);

        // Adjustment second (newer)
        $this->travelTo($baseNow->copy()->subDay());
        $adjustment = InventoryAdjustment::query()->create([
            'actor_user_id' => $this->admin->id,
            'reason' => 'Corrección inventario',
        ]);

        InventoryAdjustmentEntry::query()->create([
            'inventory_adjustment_id' => $adjustment->id,
            'subject_type' => Product::class,
            'subject_id' => $this->product->id,
            'product_id' => $this->product->id,
            'before' => ['qty_total' => 90],
            'after' => ['qty_total' => 85],
        ]);

        $this->travelBack();

        Livewire::actingAs($this->admin)
            ->test(ProductKardex::class, ['product' => (string) $this->product->id])
            ->assertSeeInOrder(['Ajuste', 'Salida']);
    }

    // =====================
    // Empty state
    // =====================

    public function test_kardex_shows_empty_state_when_no_movements(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProductKardex::class, ['product' => (string) $this->product->id])
            ->assertSee('Sin movimientos');
    }

    // =====================
    // Only applies to quantity products
    // =====================

    public function test_kardex_not_available_for_serialized_products(): void
    {
        $serializedCategory = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $serializedProduct = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $serializedCategory->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $this->actingAs($this->admin)
            ->get(route('inventory.products.kardex', ['product' => $serializedProduct->id]))
            ->assertStatus(404);
    }

    // =====================
    // UI integration
    // =====================

    public function test_product_show_displays_kardex_button_for_quantity_product(): void
    {
        $this->actingAs($this->admin)
            ->get("/inventory/products/{$this->product->id}")
            ->assertOk()
            ->assertSee('Ver kardex');
    }

    public function test_product_show_does_not_display_kardex_button_for_serialized_product(): void
    {
        $serializedCategory = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $serializedProduct = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $serializedCategory->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/inventory/products/{$serializedProduct->id}");

        $response->assertOk();
        $response->assertDontSee('Ver kardex');
    }
}
