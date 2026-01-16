<?php

namespace Tests\Feature\Movements;

use App\Actions\Movements\Assets\ReturnLoanedAsset;
use App\Enums\UserRole;
use App\Livewire\Movements\Assets\ReturnAssetForm;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AssetReturnTest extends TestCase
{
    use RefreshDatabase;

    private function createSerializedProductWithAsset(string $status = Asset::STATUS_AVAILABLE): array
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

    public function test_admin_can_access_return_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/return")
            ->assertOk();
    }

    public function test_editor_can_access_return_route(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/return")
            ->assertOk();
    }

    public function test_lector_cannot_access_return_route(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/return")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_return_livewire_action(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);

        Livewire::actingAs($lector)
            ->test(ReturnAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertForbidden();
    }

    public function test_admin_can_return_loaned_asset_and_clear_current_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $asset->update(['current_employee_id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(ReturnAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('note', 'Devolucion por fin de actividad')
            ->call('returnAsset')
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
            'type' => AssetMovement::TYPE_RETURN,
        ]);
    }

    public function test_note_is_required_for_return(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);
        $asset->update(['current_employee_id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(ReturnAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('note', '')
            ->call('returnAsset')
            ->assertHasErrors(['note']);
    }

    public function test_return_requires_employee_if_asset_has_no_current_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(ReturnAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'Devolucion (legacy sin tenencia)')
            ->call('returnAsset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $admin->id,
            'type' => AssetMovement::TYPE_RETURN,
        ]);
    }

    public function test_cannot_return_asset_that_is_not_loaned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_AVAILABLE);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new ReturnLoanedAsset;

        $this->expectException(ValidationException::class);

        $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'note' => 'Intento de devolver sin prestamo',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_asset_show_displays_return_button_for_loaned_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_LOANED);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Devolver');
    }

    public function test_asset_show_does_not_display_return_button_for_available_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_AVAILABLE);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertDontSee('bi-arrow-return-left');
    }
}
