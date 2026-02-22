<?php

namespace Tests\Feature\Catalogs;

use App\Actions\Suppliers\SearchSuppliers;
use App\Actions\Suppliers\UpsertSupplier;
use App\Enums\UserRole;
use App\Livewire\Ui\SupplierCombobox;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SupplierComboboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_suppliers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Supplier::query()->create(['name' => 'Proveedor A']);

        Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', 'Prov')
            ->assertSee('Proveedor A');
    }

    public function test_editor_can_search_suppliers(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        Supplier::query()->create(['name' => 'Proveedor B']);

        Livewire::actingAs($editor)
            ->test(SupplierCombobox::class)
            ->set('search', 'Prov')
            ->assertSee('Proveedor B');
    }

    public function test_shows_create_supplier_cta_when_no_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', 'Proveedor Nuevo')
            ->assertSee('Crear “Proveedor Nuevo”');
    }

    public function test_create_supplier_from_modal_autoselects_created_supplier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', '  Proveedor   Nuevo  ')
            ->call('openCreateSupplierModal')
            ->set('createName', '  Proveedor   Nuevo  ')
            ->set('createContact', 'Ana - 555')
            ->set('createNotes', 'Contrato anual')
            ->call('createSupplier')
            ->assertSet('showCreateModal', false)
            ->assertSet('showDropdown', false)
            ->assertSet('search', '')
            ->assertDispatched('ui:toast', type: 'success');

        $created = Supplier::query()->where('name', 'Proveedor Nuevo')->first();
        $this->assertNotNull($created);
        $this->assertSame('Ana - 555', $created->contact);
        $this->assertSame('Contrato anual', $created->notes);

        $component
            ->assertSet('supplierId', $created->id)
            ->assertSet('supplierLabel', 'Proveedor Nuevo');
    }

    public function test_create_supplier_selects_existing_instead_of_duplicating(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $existing = Supplier::query()->create(['name' => 'Proveedor Existente']);

        $component = Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', 'Proveedor Existente')
            ->call('openCreateSupplierModal')
            ->set('createName', 'Proveedor Existente')
            ->call('createSupplier')
            ->assertDispatched('ui:toast', type: 'info');

        $component->assertSet('supplierId', $existing->id);
        $this->assertSame(1, Supplier::query()->where('name', 'Proveedor Existente')->count());
    }

    public function test_create_supplier_handles_concurrent_duplicate_1062_by_selecting_existing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $inserted = false;
        Supplier::creating(function (Supplier $supplier) use (&$inserted): void {
            if ($inserted) {
                return;
            }

            $inserted = true;

            DB::table('suppliers')->insert([
                'name' => $supplier->name,
                'contact' => null,
                'notes' => null,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $component = Livewire::actingAs($admin)
                ->test(SupplierCombobox::class)
                ->set('search', 'Proveedor Carrera')
                ->call('openCreateSupplierModal')
                ->set('createName', 'Proveedor Carrera')
                ->call('createSupplier')
                ->assertDispatched('ui:toast', type: 'info');

            $existing = Supplier::query()->where('name', 'Proveedor Carrera')->first();
            $this->assertNotNull($existing);
            $this->assertSame(1, Supplier::query()->where('name', 'Proveedor Carrera')->count());

            $component->assertSet('supplierId', $existing->id);
        } finally {
            Supplier::flushEventListeners();
        }
    }

    public function test_soft_deleted_supplier_shows_trash_cta_instead_of_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::query()->create(['name' => 'En Papelera']);
        $supplier->delete();

        Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', 'En Papelera')
            ->assertSee('Existe en Papelera')
            ->assertSee('Ir a Papelera')
            ->assertDontSee('Crear “En Papelera”');
    }

    public function test_create_supplier_shows_trash_cta_in_modal_when_name_exists_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::query()->create(['name' => 'Proveedor Trashed']);
        $supplier->delete();

        Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->call('openCreateSupplierModal')
            ->set('createName', 'Proveedor Trashed')
            ->call('createSupplier')
            ->assertSet('showCreateModal', true)
            ->assertSee('Existe en Papelera')
            ->assertSee('Ir a Papelera')
            ->assertHasErrors(['createName']);
    }

    public function test_create_cta_is_hidden_when_catalogs_manage_is_denied(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        Gate::define('catalogs.manage', static fn (): bool => false);

        Livewire::actingAs($editor)
            ->test(SupplierCombobox::class)
            ->set('search', 'Proveedor Nuevo')
            ->assertDontSee('Crear “Proveedor Nuevo”');
    }

    public function test_lector_cannot_execute_supplier_combobox_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new SupplierCombobox;

        foreach ([
            'mount',
            'updatedSearch',
            'clearSelection',
            'closeDropdown',
            'retrySearch',
            'openCreateSupplierModal',
            'closeCreateSupplierModal',
            'createSupplier',
        ] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected AuthorizationException for {$method}().");
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $component->selectSupplier(1);
            $this->fail('Expected AuthorizationException for selectSupplier().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_multiple_instances_have_unique_aria_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $html = Blade::render('<livewire:ui.supplier-combobox /><livewire:ui.supplier-combobox />');

        preg_match_all('/aria-controls=\"(supplier-listbox-[^\"]+)\"/', $html, $ariaControlsMatches);
        preg_match_all('/id=\"(supplier-listbox-[^\"]+)\"/', $html, $listboxIdMatches);

        $this->assertCount(2, $ariaControlsMatches[1]);
        $this->assertCount(2, $listboxIdMatches[1]);
        $this->assertNotSame($ariaControlsMatches[1][0], $ariaControlsMatches[1][1]);
        $this->assertNotSame($listboxIdMatches[1][0], $listboxIdMatches[1][1]);
        $this->assertSame($ariaControlsMatches[1][0], $listboxIdMatches[1][0]);
        $this->assertSame($ariaControlsMatches[1][1], $listboxIdMatches[1][1]);
    }

    public function test_unexpected_search_error_reports_error_id_and_keeps_ui_usable(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->app->bind(SearchSuppliers::class, static function () {
            return new class
            {
                public function execute(mixed ...$args): void
                {
                    throw new RuntimeException('Search failure');
                }
            };
        });

        $component = Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->set('search', 'Prov')
            ->assertSee('Ocurrió un error inesperado.');

        $this->assertNotNull($component->get('errorId'));

        $component
            ->call('retrySearch')
            ->assertSet('showDropdown', true);
    }

    public function test_unexpected_create_error_reports_error_id_and_keeps_modal_open(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->app->bind(UpsertSupplier::class, static function () {
            return new class
            {
                public function execute(mixed ...$args): array
                {
                    throw new RuntimeException('Create failure');
                }
            };
        });

        $component = Livewire::actingAs($admin)
            ->test(SupplierCombobox::class)
            ->call('openCreateSupplierModal')
            ->set('createName', 'Proveedor Error')
            ->call('createSupplier')
            ->assertSet('showCreateModal', true)
            ->assertSee('Ocurrió un error inesperado.');

        $this->assertNotNull($component->get('createErrorId'));
    }
}
