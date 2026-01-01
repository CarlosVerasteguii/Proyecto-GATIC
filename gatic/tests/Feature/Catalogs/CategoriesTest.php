<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Categories\CategoriesIndex;
use App\Livewire\Catalogs\Categories\CategoryForm;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_categories_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        $this->actingAs($admin)
            ->get('/catalogs/categories')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/categories')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/catalogs/categories/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/catalogs/categories/{$category->id}/edit")
            ->assertOk();
    }

    public function test_lector_cannot_access_categories_routes(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        $this->actingAs($lector)
            ->get('/catalogs/categories')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get('/catalogs/categories/create')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get("/catalogs/categories/{$category->id}/edit")
            ->assertForbidden();
    }

    public function test_requires_asset_tag_cannot_be_true_when_not_serialized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CategoryForm::class)
            ->set('name', 'Laptops')
            ->set('is_serialized', false)
            ->set('requires_asset_tag', true)
            ->call('save')
            ->assertHasErrors(['requires_asset_tag']);
    }

    public function test_name_is_normalized_before_persisting(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CategoryForm::class)
            ->set('name', '  Foo   Bar  ')
            ->set('is_serialized', false)
            ->set('requires_asset_tag', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Foo Bar',
        ]);
    }

    public function test_name_is_unique_case_and_accent_insensitive_including_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $existing = Category::query()->create([
            'name' => 'Café',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $existing->delete();

        Livewire::actingAs($admin)
            ->test(CategoryForm::class)
            ->set('name', '  CAFE ')
            ->set('is_serialized', false)
            ->set('requires_asset_tag', false)
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_delete_is_soft_delete_and_category_disappears_from_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(CategoriesIndex::class)
            ->assertSee('Laptops')
            ->call('delete', $category->id)
            ->assertDispatched('ui:toast', type: 'success')
            ->assertDontSee('Laptops');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_delete_is_blocked_when_category_is_in_use(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Schema::dropIfExists('category_usages');
        Schema::create('category_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->timestamps();
        });

        try {
            DB::table('category_usages')->insert([
                'category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Livewire::actingAs($admin)
                ->test(CategoriesIndex::class)
                ->call('delete', $category->id)
                ->assertDispatched('ui:toast', type: 'error');

            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'deleted_at' => null,
            ]);
        } finally {
            Schema::dropIfExists('category_usages');
        }
    }

    public function test_lector_cannot_execute_categories_livewire_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $index = new CategoriesIndex;

        try {
            $index->delete(1);
            $this->fail('Expected AuthorizationException for delete().');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $form = new CategoryForm;

        try {
            $form->save();
            $this->fail('Expected AuthorizationException for save().');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }
}
