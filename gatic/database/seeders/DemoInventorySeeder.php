<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Demo inventory data for QA/dev testing.
 *
 * Creates minimal but complete data set for testing the inventory UI:
 * - 1 Category (serialized + requires asset_tag)
 * - 1 Brand
 * - 1 Location
 * - 1 Product (serialized)
 * - 1 Employee
 * - 5 Assets in different states (Disponible, Asignado, Prestado, Pendiente de Retiro, Retirado)
 */
class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        // === Catalogs ===
        $category = Category::query()->updateOrCreate(
            ['name' => 'Equipo de Cómputo'],
            [
                'is_serialized' => true,
                'requires_asset_tag' => true,
            ]
        );

        $brand = Brand::query()->updateOrCreate(
            ['name' => 'Dell']
        );

        $location = Location::query()->updateOrCreate(
            ['name' => 'Oficina Principal']
        );

        // === Employee ===
        $employee = Employee::query()->updateOrCreate(
            ['rpe' => 'RPE-001'],
            [
                'name' => 'Juan Pérez García',
                'department' => 'Tecnologías de la Información',
                'job_title' => 'Analista de Sistemas',
            ]
        );

        // === Product ===
        $product = Product::query()->updateOrCreate(
            ['name' => 'Laptop Dell Latitude 5540'],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'qty_total' => null, // Serialized products don't use qty_total
            ]
        );

        // === Assets (5 states) ===
        // 1. Disponible
        Asset::query()->updateOrCreate(
            ['product_id' => $product->id, 'serial' => 'SN-DEMO-001'],
            [
                'location_id' => $location->id,
                'asset_tag' => 'AT-001',
                'status' => Asset::STATUS_AVAILABLE,
                'current_employee_id' => null,
            ]
        );

        // 2. Asignado (with employee)
        Asset::query()->updateOrCreate(
            ['product_id' => $product->id, 'serial' => 'SN-DEMO-002'],
            [
                'location_id' => $location->id,
                'asset_tag' => 'AT-002',
                'status' => Asset::STATUS_ASSIGNED,
                'current_employee_id' => $employee->id,
            ]
        );

        // 3. Prestado (with employee)
        Asset::query()->updateOrCreate(
            ['product_id' => $product->id, 'serial' => 'SN-DEMO-003'],
            [
                'location_id' => $location->id,
                'asset_tag' => 'AT-003',
                'status' => Asset::STATUS_LOANED,
                'current_employee_id' => $employee->id,
            ]
        );

        // 4. Pendiente de Retiro
        Asset::query()->updateOrCreate(
            ['product_id' => $product->id, 'serial' => 'SN-DEMO-004'],
            [
                'location_id' => $location->id,
                'asset_tag' => 'AT-004',
                'status' => Asset::STATUS_PENDING_RETIREMENT,
                'current_employee_id' => null,
            ]
        );

        // 5. Retirado (for filter/history validation)
        Asset::query()->updateOrCreate(
            ['product_id' => $product->id, 'serial' => 'SN-DEMO-005'],
            [
                'location_id' => $location->id,
                'asset_tag' => 'AT-005',
                'status' => Asset::STATUS_RETIRED,
                'current_employee_id' => null,
            ]
        );
    }
}
