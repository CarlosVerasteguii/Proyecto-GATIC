<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_stock_alerts(): void
    {
        $this
            ->get('/alerts/stock')
            ->assertRedirect('/login');
    }

    public function test_reader_cannot_access_stock_alerts(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Lector,
        ]);

        $this
            ->actingAs($user)
            ->get('/alerts/stock')
            ->assertForbidden();
    }

    public function test_editor_can_access_stock_alerts(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Editor,
        ]);

        $this
            ->actingAs($user)
            ->get('/alerts/stock')
            ->assertOk();
    }

    public function test_admin_can_access_stock_alerts(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $this
            ->actingAs($user)
            ->get('/alerts/stock')
            ->assertOk();
    }

    public function test_stock_alerts_page_renders_with_expected_content(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $this
            ->actingAs($user)
            ->get('/alerts/stock')
            ->assertOk()
            ->assertSee('Alertas de stock bajo')
            ->assertSee('Productos por cantidad cuyo stock total está en o por debajo del umbral configurado');
    }

    public function test_stock_alerts_lists_low_stock_products(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $brand = Brand::factory()->create();

        // Low stock product
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock Product Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Product above threshold (should NOT appear)
        Product::factory()->create([
            'name' => 'Normal Stock Product Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 15,
            'low_stock_threshold' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertSee('Low Stock Product Test');
        $response->assertDontSee('Normal Stock Product Test');
    }

    public function test_stock_alerts_excludes_products_without_threshold(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $brand = Brand::factory()->create();

        // Product with no threshold (should NOT appear even with low qty)
        Product::factory()->create([
            'name' => 'No Threshold Product Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 2,
            'low_stock_threshold' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertDontSee('No Threshold Product Test');
    }

    public function test_stock_alerts_excludes_serialized_products(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $categorySerialized = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();

        // Serialized product (should NOT appear)
        Product::factory()->create([
            'name' => 'Serialized Product Test',
            'category_id' => $categorySerialized->id,
            'brand_id' => $brand->id,
            'qty_total' => null,
            'low_stock_threshold' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertDontSee('Serialized Product Test');
    }

    public function test_stock_alerts_excludes_soft_deleted_products(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $brand = Brand::factory()->create();

        // Active low stock product
        Product::factory()->create([
            'name' => 'Active Low Stock Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Soft-deleted low stock product (should NOT appear)
        $deletedProduct = Product::factory()->create([
            'name' => 'Deleted Low Stock Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 3,
            'low_stock_threshold' => 10,
        ]);
        $deletedProduct->delete();

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertSee('Active Low Stock Test');
        $response->assertDontSee('Deleted Low Stock Test');
    }

    public function test_stock_alerts_includes_product_at_threshold(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $brand = Brand::factory()->create();

        // Product exactly at threshold (should appear)
        Product::factory()->create([
            'name' => 'At Threshold Product Test',
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 10,
            'low_stock_threshold' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertSee('At Threshold Product Test');
    }

    public function test_stock_alerts_shows_empty_state_when_no_alerts(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/stock');

        $response->assertOk();
        $response->assertSee('Sin alertas de stock bajo');
    }
}
