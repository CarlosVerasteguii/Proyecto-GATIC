<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Brands\BrandForm;
use App\Livewire\Catalogs\Brands\BrandsIndex;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class BrandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_brands_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $brand = Brand::query()->create(['name' => 'HP']);

        $this->actingAs($admin)
            ->get('/catalogs/brands')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/brands')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/catalogs/brands/create')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/brands/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/catalogs/brands/{$brand->id}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/catalogs/brands/{$brand->id}/edit")
            ->assertOk();
    }

    public function test_lector_cannot_access_brands_routes(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $brand = Brand::query()->create(['name' => 'HP']);

        $this->actingAs($lector)
            ->get('/catalogs/brands')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get('/catalogs/brands/create')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get("/catalogs/brands/{$brand->id}/edit")
            ->assertForbidden();
    }

    public function test_can_create_brand_and_it_is_normalized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(BrandForm::class)
            ->set('name', '  HP  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('catalogs.brands.index'));

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
            ->test(BrandForm::class)
            ->set('name', '  cafe central ')
            ->call('save')
            ->assertHasErrors(['name' => 'unique'])
            ->assertSee('Papelera');
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

    public function test_delete_is_blocked_when_brand_is_in_use(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $brand = Brand::query()->create(['name' => 'HP']);

        Schema::dropIfExists('brand_usages');
        Schema::create('brand_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands');
            $table->timestamps();
        });

        try {
            DB::table('brand_usages')->insert([
                'brand_id' => $brand->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Livewire::actingAs($admin)
                ->test(BrandsIndex::class)
                ->call('delete', $brand->id)
                ->assertDispatched('ui:toast', type: 'error');

            $this->assertDatabaseHas('brands', [
                'id' => $brand->id,
                'deleted_at' => null,
            ]);
        } finally {
            Schema::dropIfExists('brand_usages');
        }
    }

    public function test_lector_cannot_execute_brands_livewire_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new BrandsIndex;

        try {
            $component->delete(1);
            $this->fail('Expected AuthorizationException for delete().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $form = new BrandForm;

        try {
            $form->save();
            $this->fail('Expected AuthorizationException for save().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }
}
