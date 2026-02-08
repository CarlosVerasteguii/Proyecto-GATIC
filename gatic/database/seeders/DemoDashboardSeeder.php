<?php

namespace Database\Seeders;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo dashboard data for QA/dev visualization.
 *
 * Goal: make the dashboard feel "alive" with:
 * - non-zero alert counts (loans/warranties/renewals/low stock)
 * - movement history (for charts)
 * - inventory value breakdown across brands/categories
 *
 * This seeder is additive and safe to run multiple times. It will not wipe data.
 */
class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $today = Carbon::today();

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@gatic.local'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        $locations = [
            Location::query()->updateOrCreate(['name' => 'Oficina Principal']),
            Location::query()->updateOrCreate(['name' => 'Almacén Central']),
            Location::query()->updateOrCreate(['name' => 'Subestación Norte']),
        ];

        $brands = [
            Brand::query()->updateOrCreate(['name' => 'Dell']),
            Brand::query()->updateOrCreate(['name' => 'HP']),
            Brand::query()->updateOrCreate(['name' => 'Cisco']),
            Brand::query()->updateOrCreate(['name' => 'Samsung']),
        ];

        $catComputo = Category::query()->updateOrCreate(
            ['name' => 'Equipo de Cómputo'],
            ['is_serialized' => true, 'requires_asset_tag' => true, 'default_useful_life_months' => 36],
        );

        $catComms = Category::query()->updateOrCreate(
            ['name' => 'Comunicaciones'],
            ['is_serialized' => true, 'requires_asset_tag' => false, 'default_useful_life_months' => 48],
        );

        $catConsumibles = Category::query()->updateOrCreate(
            ['name' => 'Consumibles'],
            ['is_serialized' => false, 'requires_asset_tag' => false],
        );

        for ($i = 1; $i <= 12; $i++) {
            Employee::query()->updateOrCreate(
                ['rpe' => sprintf('RPE-DASH-%03d', $i)],
                [
                    'name' => sprintf('Empleado Demo %02d', $i),
                    'department' => $i % 2 === 0 ? 'Operación' : 'TI',
                    'job_title' => $i % 3 === 0 ? 'Técnico' : 'Analista',
                ],
            );
        }

        $employees = Employee::query()
            ->where('rpe', 'like', 'RPE-DASH-%')
            ->orderBy('rpe')
            ->get(['id']);

        $pickEmployeeId = static function (int $seed) use ($employees): ?int {
            if ($employees->isEmpty()) {
                return null;
            }

            return (int) $employees[$seed % $employees->count()]->id;
        };

        $serializedProducts = [
            Product::query()->updateOrCreate(
                ['name' => 'Laptop Dell Latitude 5540'],
                ['category_id' => $catComputo->id, 'brand_id' => $brands[0]->id, 'qty_total' => null],
            ),
            Product::query()->updateOrCreate(
                ['name' => 'Laptop HP EliteBook 840'],
                ['category_id' => $catComputo->id, 'brand_id' => $brands[1]->id, 'qty_total' => null],
            ),
            Product::query()->updateOrCreate(
                ['name' => 'Switch Cisco Catalyst 9200'],
                ['category_id' => $catComms->id, 'brand_id' => $brands[2]->id, 'qty_total' => null],
            ),
        ];

        // Quantity products (drive Low Stock metric)
        Product::query()->updateOrCreate(
            ['name' => 'Cable UTP Cat6 (caja)'],
            [
                'category_id' => $catConsumibles->id,
                'brand_id' => $brands[3]->id,
                'qty_total' => 3,
                'low_stock_threshold' => 10,
            ],
        );

        Product::query()->updateOrCreate(
            ['name' => 'Conectores RJ45 (paquete)'],
            [
                'category_id' => $catConsumibles->id,
                'brand_id' => $brands[3]->id,
                'qty_total' => 18,
                'low_stock_threshold' => 10,
            ],
        );

        Product::query()->updateOrCreate(
            ['name' => 'Etiquetas Asset Tag (rollo)'],
            [
                'category_id' => $catConsumibles->id,
                'brand_id' => $brands[3]->id,
                'qty_total' => 5,
                'low_stock_threshold' => 12,
            ],
        );

        foreach ($serializedProducts as $pIdx => $product) {
            for ($i = 1; $i <= 18; $i++) {
                $seed = ($pIdx * 1000) + $i;
                $serial = sprintf('SN-DASH-%d-%04d', $product->id, $i);
                $assetTag = sprintf('AT-DASH-%d-%04d', $product->id, $i);

                $status = match (true) {
                    $i <= 9 => Asset::STATUS_AVAILABLE,
                    $i <= 13 => Asset::STATUS_ASSIGNED,
                    $i <= 16 => Asset::STATUS_LOANED,
                    $i === 17 => Asset::STATUS_PENDING_RETIREMENT,
                    default => Asset::STATUS_RETIRED,
                };

                $employeeId = in_array($status, [Asset::STATUS_ASSIGNED, Asset::STATUS_LOANED], true)
                    ? $pickEmployeeId($seed)
                    : null;

                $loanDueDate = null;
                if ($status === Asset::STATUS_LOANED) {
                    $loanDueDate = match ($i % 3) {
                        0 => $today->copy()->subDay()->toDateString(), // overdue (yesterday, to show delta)
                        1 => $today->copy()->addDays(7)->toDateString(), // due soon (boundary, to show delta)
                        default => $today->copy()->addDays(14 + ($i % 25))->toDateString(), // future
                    };
                }

                $warrantyEndDate = null;
                if ($status !== Asset::STATUS_RETIRED && $i % 2 === 0) {
                    $warrantyEndDate = match ($i % 4) {
                        0 => $today->copy()->subDay()->toDateString(), // expired (yesterday, to show delta)
                        1 => $today->copy()->addDays(30)->toDateString(), // due soon (boundary, to show delta)
                        default => $today->copy()->addDays(90 + ($i % 120))->toDateString(), // later
                    };
                }

                $expectedReplacementDate = null;
                if ($status !== Asset::STATUS_RETIRED && $i % 3 === 0) {
                    $expectedReplacementDate = match ($i % 5) {
                        0 => $today->copy()->subDay()->toDateString(), // overdue (yesterday, to show delta)
                        1 => $today->copy()->addDays(90)->toDateString(), // due soon (boundary, to show delta)
                        default => $today->copy()->addDays(120 + ($i % 240))->toDateString(), // later
                    };
                }

                $locationId = (int) $locations[$seed % count($locations)]->id;
                $baseCost = 6500 + (($seed * 73) % 28000);
                $acquisitionCost = number_format((float) $baseCost, 2, '.', '');

                Asset::query()->updateOrCreate(
                    ['product_id' => $product->id, 'serial' => $serial],
                    [
                        'location_id' => $locationId,
                        'asset_tag' => $assetTag,
                        'status' => $status,
                        'current_employee_id' => $employeeId,
                        'loan_due_date' => $loanDueDate,
                        'warranty_start_date' => $warrantyEndDate ? $today->copy()->subYear()->toDateString() : null,
                        'warranty_end_date' => $warrantyEndDate,
                        'acquisition_cost' => $acquisitionCost,
                        'acquisition_currency' => 'MXN',
                        'useful_life_months' => $product->category?->default_useful_life_months,
                        'expected_replacement_date' => $expectedReplacementDate,
                    ],
                );
            }
        }

        PendingTask::query()->updateOrCreate(
            ['description' => 'Tarea demo en proceso (dashboard)'],
            [
                'type' => PendingTaskType::Assign,
                'status' => PendingTaskStatus::Processing,
                'creator_user_id' => (int) $admin->id,
            ],
        );

        // Movement history for charts (skip if already seeded by this seeder)
        $hasDemoAssetMovements = DB::table('asset_movements')
            ->where('note', 'like', 'DEMO-DASH:%')
            ->exists();

        if (! $hasDemoAssetMovements) {
            $assetIds = Asset::query()
                ->where('serial', 'like', 'SN-DASH-%')
                ->orderBy('id')
                ->limit(60)
                ->pluck('id')
                ->all();

            $employeeIds = Employee::query()
                ->where('rpe', 'like', 'RPE-DASH-%')
                ->orderBy('id')
                ->limit(12)
                ->pluck('id')
                ->all();

            if ($assetIds !== [] && $employeeIds !== []) {
                $types = ['assign', 'unassign', 'loan', 'return'];

                for ($d = 0; $d < 45; $d++) {
                    $day = $today->copy()->subDays($d);

                    // 2-5 events per day (deterministic)
                    $events = 2 + ($d % 4);
                    for ($e = 0; $e < $events; $e++) {
                        $type = $types[($d + $e) % count($types)];
                        $assetId = (int) $assetIds[($d * 7 + $e * 3) % count($assetIds)];
                        $employeeId = (int) $employeeIds[($d + $e) % count($employeeIds)];

                        $createdAt = $day->copy()->addHours(9 + $e)->addMinutes(($d * 7) % 50);
                        $note = sprintf('DEMO-DASH:%s:%s:%02d', $day->toDateString(), $type, $e + 1);

                        DB::table('asset_movements')->insert([
                            'asset_id' => $assetId,
                            'employee_id' => $employeeId,
                            'actor_user_id' => (int) $admin->id,
                            'type' => $type,
                            'note' => $note,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }
            }
        }

        $hasDemoQtyMovements = DB::table('product_quantity_movements')
            ->where('note', 'like', 'DEMO-DASH:%')
            ->exists();

        if (! $hasDemoQtyMovements) {
            $qtyProductIds = Product::query()
                ->whereNotNull('qty_total')
                ->orderBy('id')
                ->limit(6)
                ->pluck('id')
                ->all();

            $employeeIds = Employee::query()
                ->where('rpe', 'like', 'RPE-DASH-%')
                ->orderBy('id')
                ->limit(12)
                ->pluck('id')
                ->all();

            if ($qtyProductIds !== [] && $employeeIds !== []) {
                for ($d = 0; $d < 45; $d++) {
                    $day = $today->copy()->subDays($d);
                    $events = 1 + ($d % 3);

                    for ($e = 0; $e < $events; $e++) {
                        $productId = (int) $qtyProductIds[($d + $e) % count($qtyProductIds)];
                        $employeeId = (int) $employeeIds[($d * 2 + $e) % count($employeeIds)];
                        $direction = (($d + $e) % 2) === 0 ? 'out' : 'in';

                        $qtyBefore = 20 + (($d * 3 + $e * 5) % 40);
                        $qty = 1 + (($d + $e) % 6);
                        $qtyAfter = $direction === 'out' ? max(0, $qtyBefore - $qty) : $qtyBefore + $qty;

                        $createdAt = $day->copy()->addHours(11 + $e)->addMinutes(($d * 11) % 55);
                        $note = sprintf('DEMO-DASH:%s:%s:%02d', $day->toDateString(), $direction, $e + 1);

                        DB::table('product_quantity_movements')->insert([
                            'product_id' => $productId,
                            'employee_id' => $employeeId,
                            'actor_user_id' => (int) $admin->id,
                            'direction' => $direction,
                            'qty' => $qty,
                            'qty_before' => $qtyBefore,
                            'qty_after' => $qtyAfter,
                            'note' => $note,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }
            }
        }
    }
}
