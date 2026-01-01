<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Brands\BrandsIndex;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_brands_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($admin)
            ->get('/catalogs/brands')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/brands')
            ->assertOk();
    }

    public function test_lector_cannot_access_brands_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/catalogs/brands')
            ->assertForbidden();
    }

    public function test_can_create_brand_and_it_is_normalized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(BrandsIndex::class)
            ->set('name', '  HP  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('brands', [
            'name' => 'HP',
            'deleted_at' => null,
        ]);
    }

    public function test_unique_name_is_enforced_case_accent_and_space_insensitive_including_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $existing = Brand::query()->create(['name' => 'Café  Central']);
        $existing->delete();

        Livewire::actingAs($admin)
            ->test(BrandsIndex::class)
            ->set('name', '  cafe central ')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_delete_is_soft_delete_and_brand_disappears_from_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $brand = Brand::query()->create(['name' => 'HP']);

        Livewire::actingAs($admin)
            ->test(BrandsIndex::class)
            ->assertSee('HP')
            ->call('delete', $brand->id)
            ->assertDispatched('ui:toast', type: 'success')
            ->assertDontSee('HP');

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }
}
