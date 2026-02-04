<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Contracts\ContractForm;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractsTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL TESTS ====================

    public function test_sidebar_shows_contracts_link_for_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($admin)
            ->view('layouts.partials.sidebar-nav')
            ->assertSee('/inventory/contracts', false);

        $this->actingAs($editor)
            ->view('layouts.partials.sidebar-nav')
            ->assertSee('/inventory/contracts', false);
    }

    public function test_sidebar_hides_contracts_link_for_lector(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->view('layouts.partials.sidebar-nav')
            ->assertDontSee('/inventory/contracts', false);
    }

    public function test_contract_model_has_correct_type_constants(): void
    {
        $this->assertSame('purchase', Contract::TYPE_PURCHASE);
        $this->assertSame('lease', Contract::TYPE_LEASE);
        $this->assertSame(['purchase', 'lease'], Contract::TYPES);
    }

    public function test_contract_can_be_created_with_required_fields(): void
    {
        $contract = Contract::query()->create([
            'identifier' => 'CTR-2026-001',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'identifier' => 'CTR-2026-001',
            'type' => Contract::TYPE_PURCHASE,
        ]);
    }

    public function test_contract_can_have_optional_supplier_relationship(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Proveedor Test']);

        $contract = Contract::query()->create([
            'identifier' => 'CTR-2026-002',
            'type' => Contract::TYPE_LEASE,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertInstanceOf(Supplier::class, $contract->supplier);
        $this->assertSame($supplier->id, $contract->supplier->id);
    }

    public function test_contract_can_have_date_range(): void
    {
        $contract = Contract::query()->create([
            'identifier' => 'CTR-2026-003',
            'type' => Contract::TYPE_PURCHASE,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);

        $this->assertNotNull($contract->start_date);
        $this->assertNotNull($contract->end_date);
        $this->assertSame('2026-01-01', $contract->start_date->format('Y-m-d'));
        $this->assertSame('2027-12-31', $contract->end_date->format('Y-m-d'));
    }

    public function test_contract_uses_soft_deletes(): void
    {
        $contract = Contract::query()->create([
            'identifier' => 'CTR-2026-004',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $contract->delete();

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
        $this->assertNull(Contract::query()->find($contract->id));
        $this->assertNotNull(Contract::withTrashed()->find($contract->id));
    }

    public function test_contract_identifier_must_be_unique(): void
    {
        Contract::query()->create([
            'identifier' => 'UNIQUE-ID',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Contract::query()->create([
            'identifier' => 'UNIQUE-ID',
            'type' => Contract::TYPE_LEASE,
        ]);
    }

    public function test_contract_has_many_assets_relationship(): void
    {
        $contract = Contract::query()->create([
            'identifier' => 'CTR-ASSETS',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat Serializada',
            'prefix' => 'SER',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Almacen']);

        $asset1 = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-001',
            'status' => Asset::STATUS_AVAILABLE,
            'contract_id' => $contract->id,
        ]);

        $asset2 = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-002',
            'status' => Asset::STATUS_AVAILABLE,
            'contract_id' => $contract->id,
        ]);

        $contract->refresh();

        $this->assertCount(2, $contract->assets);
        $this->assertTrue($contract->assets->contains($asset1));
        $this->assertTrue($contract->assets->contains($asset2));
    }

    public function test_asset_belongs_to_contract_relationship(): void
    {
        $contract = Contract::query()->create([
            'identifier' => 'CTR-ASSET-REL',
            'type' => Contract::TYPE_LEASE,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat Test',
            'prefix' => 'TST',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Bodega']);

        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-REL-001',
            'status' => Asset::STATUS_AVAILABLE,
            'contract_id' => $contract->id,
        ]);

        $this->assertInstanceOf(Contract::class, $asset->contract);
        $this->assertSame($contract->id, $asset->contract->id);
    }

    // ==================== RBAC TESTS ====================

    public function test_admin_and_editor_can_access_contracts_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($admin)
            ->get('/inventory/contracts')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/inventory/contracts')
            ->assertOk();
    }

    public function test_lector_cannot_access_contracts_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/inventory/contracts')
            ->assertForbidden();
    }

    public function test_admin_can_access_contract_create_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/inventory/contracts/create')
            ->assertOk();
    }

    public function test_lector_cannot_access_contract_create_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/inventory/contracts/create')
            ->assertForbidden();
    }

    public function test_lector_can_access_contract_show_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $contract = Contract::query()->create([
            'identifier' => 'CTR-LECTOR-VIEW',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $this->actingAs($lector)
            ->get("/inventory/contracts/{$contract->id}")
            ->assertOk();
    }

    public function test_lector_cannot_access_contract_edit_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $contract = Contract::query()->create([
            'identifier' => 'CTR-LECTOR-NOEDIT',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $this->actingAs($lector)
            ->get("/inventory/contracts/{$contract->id}/edit")
            ->assertForbidden();
    }

    // ==================== LIVEWIRE FORM TESTS ====================

    public function test_can_create_contract_via_livewire(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-LW-001')
            ->set('type', Contract::TYPE_PURCHASE)
            ->set('start_date', '2026-01-01')
            ->set('end_date', '2026-12-31')
            ->set('notes', 'Notas de prueba')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.contracts.index'));

        $this->assertDatabaseHas('contracts', [
            'identifier' => 'CTR-LW-001',
            'type' => Contract::TYPE_PURCHASE,
            'notes' => 'Notas de prueba',
        ]);
    }

    public function test_contract_form_validates_required_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors(['identifier', 'type']);
    }

    public function test_contract_form_validates_unique_identifier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Contract::query()->create([
            'identifier' => 'EXISTING-ID',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'EXISTING-ID')
            ->set('type', Contract::TYPE_LEASE)
            ->call('save')
            ->assertHasErrors(['identifier']);
    }

    public function test_contract_form_validates_type_in_allowed_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-INVALID-TYPE')
            ->set('type', 'invalid_type')
            ->call('save')
            ->assertHasErrors(['type']);
    }

    public function test_contract_form_validates_end_date_after_start_date(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-DATE-INVALID')
            ->set('type', Contract::TYPE_PURCHASE)
            ->set('start_date', '2026-12-31')
            ->set('end_date', '2026-01-01')
            ->call('save')
            ->assertHasErrors(['end_date']);
    }

    public function test_contract_form_validates_supplier_exists_and_not_soft_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $deletedSupplier = Supplier::query()->create(['name' => 'Proveedor Eliminado']);
        $deletedSupplier->delete();

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-DELETED-SUP')
            ->set('type', Contract::TYPE_PURCHASE)
            ->set('supplier_id', $deletedSupplier->id)
            ->call('save')
            ->assertHasErrors(['supplier_id']);
    }

    public function test_can_create_contract_with_valid_supplier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::query()->create(['name' => 'Proveedor Valido']);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-WITH-SUP')
            ->set('type', Contract::TYPE_LEASE)
            ->set('supplier_id', $supplier->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.contracts.index'));

        $this->assertDatabaseHas('contracts', [
            'identifier' => 'CTR-WITH-SUP',
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_can_update_existing_contract(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $contract = Contract::query()->create([
            'identifier' => 'CTR-UPDATE-ORIG',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class, ['contract' => (string) $contract->id])
            ->set('identifier', 'CTR-UPDATE-NEW')
            ->set('type', Contract::TYPE_LEASE)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.contracts.index'));

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'identifier' => 'CTR-UPDATE-NEW',
            'type' => Contract::TYPE_LEASE,
        ]);
    }

    // ==================== ASSET LINKING TESTS ====================

    public function test_can_link_assets_to_contract_on_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $category = Category::query()->create([
            'name' => 'Cat Link Test',
            'prefix' => 'LNK',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Almacen Link']);

        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-LINK-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-LINK')
            ->set('type', Contract::TYPE_PURCHASE)
            ->set('assetSearch', 'SN-LINK-001')
            ->call('searchAssets')
            ->call('linkAsset', $asset->id)
            ->call('save')
            ->assertHasNoErrors();

        $contract = Contract::query()->where('identifier', 'CTR-LINK')->first();
        $asset->refresh();

        $this->assertSame($contract->id, $asset->contract_id);
    }

    public function test_linking_asset_from_other_contract_requires_confirmation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $existingContract = Contract::query()->create([
            'identifier' => 'CTR-EXISTING',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat Reassign',
            'prefix' => 'REA',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Almacen Reassign']);

        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-REASSIGN-001',
            'status' => Asset::STATUS_AVAILABLE,
            'contract_id' => $existingContract->id,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('identifier', 'CTR-NEW-REASSIGN')
            ->set('type', Contract::TYPE_LEASE)
            ->set('assetSearch', 'SN-REASSIGN-001')
            ->call('searchAssets')
            ->call('linkAsset', $asset->id)
            ->assertSet('pendingReassignAssetId', $asset->id)
            ->assertSet('linkedAssetIds', [])
            ->call('linkAsset', $asset->id)
            ->assertSet('pendingReassignAssetId', null)
            ->call('save')
            ->assertHasNoErrors();

        $newContract = Contract::query()->where('identifier', 'CTR-NEW-REASSIGN')->first();
        $this->assertNotNull($newContract);

        $asset->refresh();
        $this->assertSame($newContract->id, $asset->contract_id);
    }

    public function test_can_unlink_asset_from_contract(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $contract = Contract::query()->create([
            'identifier' => 'CTR-UNLINK',
            'type' => Contract::TYPE_PURCHASE,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat Unlink',
            'prefix' => 'UNL',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Almacen Unlink']);

        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-UNLINK-001',
            'status' => Asset::STATUS_AVAILABLE,
            'contract_id' => $contract->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ContractForm::class, ['contract' => (string) $contract->id])
            ->call('unlinkAsset', $asset->id)
            ->call('save')
            ->assertHasNoErrors();

        $asset->refresh();

        $this->assertNull($asset->contract_id);
    }

    public function test_soft_deleted_assets_do_not_appear_in_search(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $category = Category::query()->create([
            'name' => 'Cat Deleted Asset',
            'prefix' => 'DEL',
            'is_serialized' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $location = Location::query()->create(['name' => 'Almacen Del']);

        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN-DELETED-ASSET',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $asset->delete();

        $component = Livewire::actingAs($admin)
            ->test(ContractForm::class)
            ->set('assetSearch', 'SN-DELETED-ASSET')
            ->call('searchAssets');

        $this->assertEmpty($component->get('searchResults'));
    }
}
