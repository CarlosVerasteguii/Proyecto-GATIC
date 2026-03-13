<?php

namespace Tests\Feature\Admin\Trash;

use App\Enums\UserRole;
use App\Livewire\Admin\Trash\TrashIndex;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTrashViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_trash_honors_tab_query_string(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::withQueryParams(['tab' => 'employees'])
            ->actingAs($admin)
            ->test(TrashIndex::class)
            ->assertSet('tab', 'employees');
    }

    public function test_admin_can_search_assets_in_trash(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $location = Location::query()->create(['name' => 'Bodega']);
        $product = Product::query()->create([
            'name' => 'Laptop de prueba',
            'category_id' => $category->id,
        ]);
        $matchingAsset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'MATCH-001',
            'asset_tag' => 'AT-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $otherAsset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'OTHER-002',
            'asset_tag' => 'AT-002',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $matchingAsset->delete();
        $otherAsset->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'assets')
            ->set('search', 'MATCH-001')
            ->assertSee('MATCH-001')
            ->assertDontSee('OTHER-002');
    }
}
