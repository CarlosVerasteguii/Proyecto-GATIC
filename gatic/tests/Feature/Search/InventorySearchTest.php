<?php

namespace Tests\Feature\Search;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InventorySearchTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // AC3 - RBAC server-side tests
    // =========================================================================

    public function test_guest_cannot_access_inventory_search(): void
    {
        $this->get('/inventory/search')
            ->assertRedirect('/login');
    }

    public function test_user_without_inventory_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => UserRole::Lector]);

        Gate::define('inventory.view', static fn (User $u): bool => false);

        $this->actingAs($user)
            ->get('/inventory/search')
            ->assertForbidden();
    }

    public function test_admin_can_access_inventory_search(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/inventory/search')
            ->assertOk();
    }

    public function test_editor_can_access_inventory_search(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/inventory/search')
            ->assertOk();
    }

    public function test_lector_can_access_inventory_search(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/inventory/search')
            ->assertOk();
    }

    // =========================================================================
    // AC1 - Search by product name tests
    // =========================================================================

    public function test_search_by_product_name_returns_matching_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Dell Latitude 5520',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Product::query()->create([
            'name' => 'HP EliteBook 840',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=Dell')
            ->assertOk()
            ->assertSee('Dell Latitude 5520')
            ->assertDontSee('HP EliteBook 840');
    }

    public function test_search_requires_minimum_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=a')
            ->assertOk()
            ->assertSee('Ingresa al menos 2 caracteres');
    }

    public function test_search_shows_no_results_message(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=nonexistent')
            ->assertOk()
            ->assertSee('No se encontraron resultados');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Product_1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Product::query()->create([
            'name' => 'Product X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=Product_1')
            ->assertOk()
            ->assertSee('Product_1')
            ->assertDontSee('Product X1');
    }

    // =========================================================================
    // AC2 - Exact match by asset_tag and serial tests
    // =========================================================================

    public function test_exact_match_by_asset_tag_redirects_to_asset_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => true,
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
            'serial' => 'SN12345',
            'asset_tag' => 'GATIC-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=GATIC-001')
            ->assertRedirect(route('inventory.products.assets.show', [
                'product' => $product->id,
                'asset' => $asset->id,
            ]));
    }

    public function test_exact_match_by_asset_tag_is_case_insensitive(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => true,
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
            'serial' => 'SN12345',
            'asset_tag' => 'GATIC-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=gatic-001')
            ->assertRedirect(route('inventory.products.assets.show', [
                'product' => $product->id,
                'asset' => $asset->id,
            ]));
    }

    public function test_exact_match_by_unique_serial_redirects_to_asset_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
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
            'serial' => 'UNIQUE-SERIAL-123',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=UNIQUE-SERIAL-123')
            ->assertRedirect(route('inventory.products.assets.show', [
                'product' => $product->id,
                'asset' => $asset->id,
            ]));
    }

    public function test_ambiguous_serial_match_shows_list_instead_of_redirect(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        $product1 = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $product2 = Product::query()->create([
            'name' => 'HP EliteBook',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        // Same serial in different products (valid per domain rules: unique per product_id+serial)
        Asset::query()->create([
            'product_id' => $product1->id,
            'location_id' => $location->id,
            'serial' => 'SHARED-SERIAL',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Asset::query()->create([
            'product_id' => $product2->id,
            'location_id' => $location->id,
            'serial' => 'SHARED-SERIAL',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=SHARED-SERIAL')
            ->assertOk()
            ->assertSee('Dell X1')
            ->assertSee('HP EliteBook')
            ->assertSee('SHARED-SERIAL');
    }

    public function test_search_by_serial_partial_match_returns_assets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
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
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-ABC-123',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-ABC-456',
            'asset_tag' => null,
            'status' => Asset::STATUS_ASSIGNED,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-XYZ-789',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/search?q=SN-ABC')
            ->assertOk()
            ->assertSee('SN-ABC-123')
            ->assertSee('SN-ABC-456')
            ->assertDontSee('SN-XYZ-789');
    }

    // =========================================================================
    // Query string persistence (UX)
    // =========================================================================

    public function test_search_term_is_persisted_in_url(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'TestQuery Laptop',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        // Test that the URL query parameter q=testquery triggers search and shows results
        // This validates that the #[Url(as: 'q')] attribute is working
        $this->actingAs($admin)
            ->get('/inventory/search?q=TestQuery')
            ->assertOk()
            ->assertSee('TestQuery Laptop');
    }
}
