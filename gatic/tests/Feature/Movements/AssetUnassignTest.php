<?php

namespace Tests\Feature\Movements;

use App\Enums\UserRole;
use App\Livewire\Movements\Assets\UnassignAssetForm;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetUnassignTest extends TestCase
{
    use RefreshDatabase;

    private function createSerializedProductWithAsset(string $status = Asset::STATUS_ASSIGNED): array
    {
        $location = Location::query()->create(['name' => 'Almacen']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-001',
            'asset_tag' => null,
            'status' => $status,
        ]);

        return compact('location', 'category', 'product', 'asset');
    }

    public function test_admin_can_access_unassign_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/unassign")
            ->assertOk();
    }

    public function test_editor_can_access_unassign_route(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/unassign")
            ->assertOk();
    }

    public function test_lector_cannot_access_unassign_route(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/unassign")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_unassign_livewire_action(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        Livewire::actingAs($lector)
            ->test(UnassignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertForbidden();
    }

    public function test_admin_can_unassign_assigned_asset_and_clear_current_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $asset->update(['current_employee_id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(UnassignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('note', 'Desasignación por cambio de equipo')
            ->call('unassignAsset')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $admin->id,
            'type' => AssetMovement::TYPE_UNASSIGN,
        ]);
    }

    public function test_note_is_required_for_unassign(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);
        $asset->update(['current_employee_id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(UnassignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('note', '')
            ->call('unassignAsset')
            ->assertHasErrors(['note']);
    }

    public function test_note_must_have_minimum_length_for_unassign(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);
        $asset->update(['current_employee_id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(UnassignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('note', '1234')
            ->call('unassignAsset')
            ->assertHasErrors(['note']);
    }

    public function test_unassign_requires_employee_if_asset_has_no_current_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(UnassignAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'Desasignación (legacy sin tenencia)')
            ->call('unassignAsset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $admin->id,
            'type' => AssetMovement::TYPE_UNASSIGN,
        ]);
    }

    public function test_unassign_route_redirects_if_asset_is_not_assigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_AVAILABLE);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/unassign")
            ->assertRedirect(route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        $this->assertDatabaseCount('asset_movements', 0);
    }
}

