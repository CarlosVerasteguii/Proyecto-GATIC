<?php

namespace Tests\Feature\Catalogs;

use App\Enums\UserRole;
use App\Livewire\Catalogs\Suppliers\SuppliersIndex;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_suppliers_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($admin)
            ->get('/catalogs/suppliers')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/catalogs/suppliers')
            ->assertOk();
    }

    public function test_lector_cannot_access_suppliers_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/catalogs/suppliers')
            ->assertForbidden();
    }

    public function test_can_create_supplier_and_it_is_normalized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SuppliersIndex::class)
            ->set('name', '  Proveedor  Uno  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Proveedor Uno',
            'deleted_at' => null,
        ]);
    }

    public function test_can_create_supplier_with_contact_and_notes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SuppliersIndex::class)
            ->set('name', 'Proveedor Test')
            ->set('contact', 'Juan Perez - 555-1234')
            ->set('notes', 'Entrega los lunes.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Proveedor Test',
            'contact' => 'Juan Perez - 555-1234',
            'notes' => 'Entrega los lunes.',
        ]);
    }

    public function test_unique_name_is_enforced_case_accent_and_space_insensitive_including_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $existing = Supplier::query()->create(['name' => 'Café  Central']);
        $existing->delete();

        Livewire::actingAs($admin)
            ->test(SuppliersIndex::class)
            ->set('name', '  cafe central ')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_delete_is_soft_delete_and_supplier_disappears_from_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::query()->create(['name' => 'Proveedor XYZ']);

        Livewire::actingAs($admin)
            ->test(SuppliersIndex::class)
            ->assertSeeHtml('<td>Proveedor XYZ</td>')
            ->call('delete', $supplier->id)
            ->assertDispatched('ui:toast', type: 'success')
            ->assertDontSeeHtml('<td>Proveedor XYZ</td>');

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_delete_is_blocked_when_supplier_is_in_use_by_a_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::query()->create(['name' => 'Proveedor en Uso']);

        Product::factory()
            ->withSupplier($supplier)
            ->create();

        Livewire::actingAs($admin)
            ->test(SuppliersIndex::class)
            ->call('delete', $supplier->id)
            ->assertDispatched('ui:toast', type: 'error');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'deleted_at' => null,
        ]);
    }

    public function test_lector_cannot_execute_suppliers_livewire_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new SuppliersIndex;

        try {
            $component->save();
            $this->fail('Expected AuthorizationException for save().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $component->edit(1);
            $this->fail('Expected AuthorizationException for edit().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $component->delete(1);
            $this->fail('Expected AuthorizationException for delete().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }
}
