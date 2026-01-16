<?php

namespace Tests\Feature\Movements;

use App\Actions\Movements\Assets\LoanAssetToEmployee;
use App\Enums\UserRole;
use App\Livewire\Movements\Assets\LoanAssetForm;
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

class AssetLoanTest extends TestCase
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

    public function test_admin_can_access_loan_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/loan")
            ->assertOk();
    }

    public function test_editor_can_access_loan_route(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/loan")
            ->assertOk();
    }

    public function test_lector_cannot_access_loan_route(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/loan")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_loan_livewire_action(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        Livewire::actingAs($lector)
            ->test(LoanAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertForbidden();
    }

    public function test_admin_can_loan_available_asset_to_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
            'department' => 'IT',
        ]);

        Livewire::actingAs($admin)
            ->test(LoanAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', 'Prestamo para soporte en sitio')
            ->call('loan')
            ->assertHasNoErrors()
            ->assertRedirect(route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_LOANED,
            'current_employee_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('asset_movements', [
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $admin->id,
            'type' => AssetMovement::TYPE_LOAN,
        ]);
    }

    public function test_note_is_required_for_loan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        Livewire::actingAs($admin)
            ->test(LoanAssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('employeeId', $employee->id)
            ->set('note', '')
            ->call('loan')
            ->assertHasErrors(['note']);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);
    }

    public function test_cannot_loan_assigned_asset_message_is_actionable(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);
        $employee = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $action = new LoanAssetToEmployee;

        $this->expectException(ValidationException::class);

        try {
            $action->execute([
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'note' => 'Intento de prestar activo asignado',
                'actor_user_id' => $admin->id,
            ]);
        } catch (ValidationException $e) {
            $this->assertStringContainsString('desasign', $e->errors()['asset_id'][0] ?? '');
            throw $e;
        }
    }

    public function test_asset_show_displays_loan_button_for_available_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Prestar');
    }

    public function test_asset_show_does_not_display_loan_button_for_assigned_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ['product' => $product, 'asset' => $asset] = $this->createSerializedProductWithAsset(Asset::STATUS_ASSIGNED);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertDontSee('bi-box-arrow-up-right');
    }
}
