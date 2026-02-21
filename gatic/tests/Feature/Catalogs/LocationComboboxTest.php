<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Ui\LocationCombobox;
use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class LocationComboboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_locations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Location::query()->create(['name' => 'Bodega']);

        Livewire::actingAs($admin)
            ->test(LocationCombobox::class)
            ->set('search', 'Bod')
            ->assertSee('Bodega');
    }

    public function test_shows_create_location_cta_when_no_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(LocationCombobox::class)
            ->set('search', 'Ubicacion Nueva')
            ->assertSee('Crear “Ubicacion Nueva”');
    }

    public function test_create_location_from_combobox_autoselects_created_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(LocationCombobox::class)
            ->set('search', '  Ubicacion   Nueva  ')
            ->call('createFromSearch')
            ->assertSet('showDropdown', false)
            ->assertSet('search', '')
            ->assertDispatched('ui:toast', type: 'success');

        $created = Location::query()->where('name', 'Ubicacion Nueva')->first();
        $this->assertNotNull($created);
        $component
            ->assertSet('locationId', $created->id)
            ->assertSet('locationLabel', 'Ubicacion Nueva');
    }

    public function test_create_location_selects_existing_instead_of_duplicating(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(LocationCombobox::class)
            ->set('search', 'Sitio A');

        $existing = Location::query()->create(['name' => 'Sitio A']);

        $component
            ->call('createFromSearch')
            ->assertDispatched('ui:toast', type: 'info')
            ->assertSet('locationId', $existing->id);

        $this->assertSame(1, Location::query()->where('name', 'Sitio A')->count());
    }

    public function test_create_location_handles_concurrent_duplicate_1062_by_selecting_existing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $inserted = false;
        Location::creating(function (Location $location) use (&$inserted): void {
            if ($inserted) {
                return;
            }

            $inserted = true;

            DB::table('locations')->insert([
                'name' => $location->name,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $component = Livewire::actingAs($admin)
                ->test(LocationCombobox::class)
                ->set('search', 'Ubicacion Carrera')
                ->call('createFromSearch')
                ->assertDispatched('ui:toast', type: 'info');

            $existing = Location::query()->where('name', 'Ubicacion Carrera')->first();
            $this->assertNotNull($existing);
            $this->assertSame(1, Location::query()->where('name', 'Ubicacion Carrera')->count());

            $component->assertSet('locationId', $existing->id);
        } finally {
            Location::flushEventListeners();
        }
    }

    public function test_soft_deleted_location_shows_trash_cta_instead_of_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'En Papelera']);
        $location->delete();

        Livewire::actingAs($admin)
            ->test(LocationCombobox::class)
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
            ->test(LocationCombobox::class)
            ->set('search', 'Ubicacion Nueva')
            ->assertDontSee('Crear “Ubicacion Nueva”');
    }

    public function test_lector_cannot_execute_location_combobox_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new LocationCombobox;

        foreach (['mount', 'updatedSearch', 'clearSelection', 'closeDropdown', 'retrySearch', 'createFromSearch'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected AuthorizationException for {$method}().");
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $component->selectLocation(1);
            $this->fail('Expected AuthorizationException for selectLocation().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_multiple_instances_have_unique_aria_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $html = Blade::render('<livewire:ui.location-combobox /><livewire:ui.location-combobox />');

        preg_match_all('/aria-controls=\"(location-listbox-[^\"]+)\"/', $html, $ariaControlsMatches);
        preg_match_all('/id=\"(location-listbox-[^\"]+)\"/', $html, $listboxIdMatches);

        $this->assertCount(2, $ariaControlsMatches[1]);
        $this->assertCount(2, $listboxIdMatches[1]);
        $this->assertNotSame($ariaControlsMatches[1][0], $ariaControlsMatches[1][1]);
        $this->assertNotSame($listboxIdMatches[1][0], $listboxIdMatches[1][1]);
        $this->assertSame($ariaControlsMatches[1][0], $listboxIdMatches[1][0]);
        $this->assertSame($ariaControlsMatches[1][1], $listboxIdMatches[1][1]);
    }
}
