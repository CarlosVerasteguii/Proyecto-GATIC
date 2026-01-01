<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Trash\CatalogsTrash;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogsTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_catalogs_trash_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/catalogs/trash')
            ->assertOk();
    }

    public function test_editor_and_lector_are_forbidden_from_catalogs_trash_page(): void
    {
        $roles = [UserRole::Editor, UserRole::Lector];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/catalogs/trash')
                ->assertForbidden();
        }
    }

    public function test_admin_can_restore_soft_deleted_brand(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $brand = Brand::query()->create(['name' => 'HP']);
        $brand->delete();

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);

        Livewire::actingAs($admin)
            ->test(CatalogsTrash::class)
            ->call('restore', 'brands', $brand->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/catalogs/brands')
            ->assertOk()
            ->assertSee('<td>HP</td>', false);
    }

    public function test_admin_can_restore_soft_deleted_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        Livewire::actingAs($admin)
            ->test(CatalogsTrash::class)
            ->call('restore', 'categories', $category->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/catalogs/categories')
            ->assertOk()
            ->assertSee('Laptops');
    }

    public function test_admin_can_restore_soft_deleted_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Bodega Central']);
        $location->delete();

        $this->assertSoftDeleted('locations', ['id' => $location->id]);

        Livewire::actingAs($admin)
            ->test(CatalogsTrash::class)
            ->call('restore', 'locations', $location->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/catalogs/locations')
            ->assertOk()
            ->assertSee('Bodega Central');
    }

    public function test_editor_and_lector_cannot_execute_restore_action(): void
    {
        $roles = [UserRole::Editor, UserRole::Lector];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user);

            $component = new CatalogsTrash;

            try {
                $component->restore('brands', 1);
                $this->fail('Expected AuthorizationException for restore().');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }
}
