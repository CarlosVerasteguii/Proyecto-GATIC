<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Locations\LocationForm;
use App\Livewire\Catalogs\Locations\LocationsIndex;
use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class LocationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_locations_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $location = Location::query()->create(['name' => 'Bodega Central']);

        $this->actingAs($admin)
            ->get('/catalogs/locations')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/locations')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/catalogs/locations/create')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/locations/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/catalogs/locations/{$location->id}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/catalogs/locations/{$location->id}/edit")
            ->assertOk();
    }

    public function test_lector_cannot_access_locations_routes(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $location = Location::query()->create(['name' => 'Bodega Central']);

        $this->actingAs($lector)
            ->get('/catalogs/locations')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get('/catalogs/locations/create')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get("/catalogs/locations/{$location->id}/edit")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_locations_livewire_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new LocationsIndex;

        try {
            $component->delete(1);
            $this->fail('Expected AuthorizationException for delete().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $form = new LocationForm;

        try {
            $form->save();
            $this->fail('Expected AuthorizationException for save().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_can_create_location_and_it_is_normalized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(LocationForm::class)
            ->set('name', '  Bodega   Central  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('catalogs.locations.index'));

        $this->assertDatabaseHas('locations', [
            'name' => 'Bodega Central',
            'deleted_at' => null,
        ]);
    }

    public function test_unique_name_is_enforced_case_accent_and_space_insensitive_including_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $existing = Location::query()->create(['name' => 'Café  Central']);
        $existing->delete();

        Livewire::actingAs($admin)
            ->test(LocationForm::class)
            ->set('name', '  cafe central ')
            ->call('save')
            ->assertHasErrors(['name' => 'unique'])
            ->assertSee('Papelera');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Location::query()->create(['name' => 'A_B']);
        Location::query()->create(['name' => 'ABC']);
        Location::query()->create(['name' => '100% Real']);
        Location::query()->create(['name' => '1000 Real']);

        Livewire::actingAs($admin)
            ->test(LocationsIndex::class)
            ->set('search', '_')
            ->assertSee('A_B')
            ->assertDontSee('ABC');

        Livewire::actingAs($admin)
            ->test(LocationsIndex::class)
            ->set('search', '%')
            ->assertSee('100% Real')
            ->assertDontSee('1000 Real');
    }

    public function test_delete_is_soft_delete_and_location_disappears_from_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Bodega Central']);

        Livewire::actingAs($admin)
            ->test(LocationsIndex::class)
            ->assertSee('Bodega Central')
            ->call('delete', $location->id)
            ->assertDispatched('ui:toast', type: 'success')
            ->assertDontSee('Bodega Central');

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_delete_is_blocked_when_location_is_in_use(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Bodega Central']);

        Schema::dropIfExists('location_usages');
        Schema::create('location_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');
            $table->timestamps();
        });

        try {
            DB::table('location_usages')->insert([
                'location_id' => $location->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Livewire::actingAs($admin)
                ->test(LocationsIndex::class)
                ->call('delete', $location->id)
                ->assertDispatched('ui:toast', type: 'error');

            $this->assertDatabaseHas('locations', [
                'id' => $location->id,
                'deleted_at' => null,
            ]);
        } finally {
            Schema::dropIfExists('location_usages');
        }
    }
}
