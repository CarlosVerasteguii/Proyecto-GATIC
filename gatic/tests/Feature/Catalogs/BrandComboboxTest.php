<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Ui\BrandCombobox;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class BrandComboboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_brands(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Brand::query()->create(['name' => 'HP']);

        Livewire::actingAs($admin)
            ->test(BrandCombobox::class)
            ->set('search', 'HP')
            ->assertSee('HP');
    }

    public function test_shows_create_brand_cta_when_no_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(BrandCombobox::class)
            ->set('search', 'Marca Nueva')
            ->assertSee('Crear “Marca Nueva”');
    }

    public function test_create_brand_from_combobox_autoselects_created_brand(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(BrandCombobox::class)
            ->set('search', '  Marca   Nueva  ')
            ->call('createFromSearch')
            ->assertSet('showDropdown', false)
            ->assertSet('search', '')
            ->assertDispatched('ui:toast', type: 'success');

        $created = Brand::query()->where('name', 'Marca Nueva')->first();
        $this->assertNotNull($created);
        $component
            ->assertSet('brandId', $created->id)
            ->assertSet('brandLabel', 'Marca Nueva');
    }

    public function test_create_brand_selects_existing_instead_of_duplicating(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(BrandCombobox::class)
            ->set('search', 'Marca Existente');

        $existing = Brand::query()->create(['name' => 'Marca Existente']);

        $component
            ->call('createFromSearch')
            ->assertDispatched('ui:toast', type: 'info')
            ->assertSet('brandId', $existing->id);

        $this->assertSame(1, Brand::query()->where('name', 'Marca Existente')->count());
    }

    public function test_create_brand_handles_concurrent_duplicate_1062_by_selecting_existing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $inserted = false;
        Brand::creating(function (Brand $brand) use (&$inserted): void {
            if ($inserted) {
                return;
            }

            $inserted = true;

            DB::table('brands')->insert([
                'name' => $brand->name,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $component = Livewire::actingAs($admin)
                ->test(BrandCombobox::class)
                ->set('search', 'Marca Carrera')
                ->call('createFromSearch')
                ->assertDispatched('ui:toast', type: 'info');

            $existing = Brand::query()->where('name', 'Marca Carrera')->first();
            $this->assertNotNull($existing);
            $this->assertSame(1, Brand::query()->where('name', 'Marca Carrera')->count());

            $component->assertSet('brandId', $existing->id);
        } finally {
            Brand::flushEventListeners();
        }
    }

    public function test_soft_deleted_brand_shows_trash_cta_instead_of_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $brand = Brand::query()->create(['name' => 'En Papelera']);
        $brand->delete();

        Livewire::actingAs($admin)
            ->test(BrandCombobox::class)
            ->set('search', 'En Papelera')
            ->assertSee('Existe en Papelera')
            ->assertSee('Ir a Papelera')
            ->assertDontSee('Crear “En Papelera”');
    }

    public function test_create_cta_is_hidden_when_catalogs_manage_is_denied(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        Gate::define('catalogs.manage', static fn (): bool => false);

        Livewire::actingAs($editor)
            ->test(BrandCombobox::class)
            ->set('search', 'Marca Nueva')
            ->assertDontSee('Crear “Marca Nueva”');
    }

    public function test_lector_cannot_execute_brand_combobox_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new BrandCombobox;

        foreach (['mount', 'updatedSearch', 'clearSelection', 'closeDropdown', 'retrySearch', 'createFromSearch'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected AuthorizationException for {$method}().");
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $component->selectBrand(1);
            $this->fail('Expected AuthorizationException for selectBrand().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_multiple_instances_have_unique_aria_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $html = Blade::render('<livewire:ui.brand-combobox /><livewire:ui.brand-combobox />');

        preg_match_all('/aria-controls=\"(brand-listbox-[^\"]+)\"/', $html, $ariaControlsMatches);
        preg_match_all('/id=\"(brand-listbox-[^\"]+)\"/', $html, $listboxIdMatches);

        $this->assertCount(2, $ariaControlsMatches[1]);
        $this->assertCount(2, $listboxIdMatches[1]);
        $this->assertNotSame($ariaControlsMatches[1][0], $ariaControlsMatches[1][1]);
        $this->assertNotSame($listboxIdMatches[1][0], $listboxIdMatches[1][1]);
        $this->assertSame($ariaControlsMatches[1][0], $listboxIdMatches[1][0]);
        $this->assertSame($ariaControlsMatches[1][1], $listboxIdMatches[1][1]);
    }
}
