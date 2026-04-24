<?php

declare(strict_types=1);

namespace Tests\Feature\Dev;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_role_check_constraint_rejects_invalid_values(): void
    {
        $this->skipUnlessMySql();

        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Usuario inválido',
            'email' => 'invalid-role@example.test',
            'password' => bcrypt('password'),
            'role' => 'SuperAdmin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_assets_status_check_constraint_rejects_invalid_values(): void
    {
        $this->skipUnlessMySql();

        $category = Category::factory()->create([
            'is_serialized' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);
        $location = Location::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('assets')->insert([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'INVALID-STATUS-001',
            'asset_tag' => 'INVALID-STATUS-001',
            'status' => 'En tránsito',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_pending_tasks_include_indexes_for_latest_and_type_filtered_list_queries(): void
    {
        $this->skipUnlessMySql();

        $indexes = collect(DB::select('SHOW INDEX FROM pending_tasks'))
            ->groupBy('Key_name')
            ->map(function (Collection $rows): array {
                return array_values(array_map(
                    static fn (object $row): string => (string) $row->Column_name,
                    $rows->all(),
                ));
            });

        $this->assertSame(
            ['created_at'],
            $indexes->get('pending_tasks_created_at_index')
        );
        $this->assertSame(
            ['type', 'created_at'],
            $indexes->get('pending_tasks_type_created_at_index')
        );
    }

    private function skipUnlessMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('SchemaHardeningTest requiere MySQL.');
        }
    }
}
