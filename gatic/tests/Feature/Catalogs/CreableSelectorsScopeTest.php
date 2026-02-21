<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Inventory\Assets\AssetsGlobalIndex;
use App\Livewire\Inventory\Products\ProductsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreableSelectorsScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_filters_do_not_render_creable_brand_cta(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->assertSeeHtml('id="filter-brand"')
            ->assertDontSee('brand-option-create-')
            ->assertDontSee('location-option-create-');
    }

    public function test_assets_global_index_filters_do_not_render_creable_location_or_brand_cta(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(AssetsGlobalIndex::class)
            ->assertSeeHtml('id="filter-location"')
            ->assertSeeHtml('id="filter-brand"')
            ->assertDontSee('location-option-create-')
            ->assertDontSee('brand-option-create-');
    }
}
