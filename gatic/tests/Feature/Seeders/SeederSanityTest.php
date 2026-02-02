<?php

namespace Tests\Feature\Seeders;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Validates that DatabaseSeeder creates expected demo data.
 *
 * This test runs migrate:fresh --seed equivalent and asserts
 * the data required for QA velocity is present.
 */
#[Group('seeder')]
class SeederSanityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually run the seeder to ensure it runs after RefreshDatabase
        $this->seed(DatabaseSeeder::class);
    }

    // === Users ===

    public function test_creates_admin_user(): void
    {
        $user = User::query()->where('email', 'admin@gatic.local')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Admin', $user->role->value);
    }

    public function test_creates_editor_user(): void
    {
        $user = User::query()->where('email', 'editor@gatic.local')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Editor', $user->role->value);
    }

    public function test_creates_editor2_user_for_concurrency_testing(): void
    {
        $user = User::query()->where('email', 'editor2@gatic.local')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Editor', $user->role->value);
    }

    public function test_creates_lector_user(): void
    {
        $user = User::query()->where('email', 'lector@gatic.local')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Lector', $user->role->value);
    }

    // === Catalogs ===

    public function test_creates_serialized_category(): void
    {
        $category = Category::query()->where('name', 'Equipo de Cómputo')->first();

        $this->assertNotNull($category);
        $this->assertTrue($category->is_serialized);
        $this->assertTrue($category->requires_asset_tag);
    }

    public function test_creates_brand(): void
    {
        $brand = Brand::query()->where('name', 'Dell')->first();

        $this->assertNotNull($brand);
    }

    public function test_creates_location(): void
    {
        $location = Location::query()->where('name', 'Oficina Principal')->first();

        $this->assertNotNull($location);
    }

    // === Product ===

    public function test_creates_serialized_product(): void
    {
        $product = Product::query()->where('name', 'Laptop Dell Latitude 5540')->first();

        $this->assertNotNull($product);
        $this->assertNotNull($product->category);
        $this->assertTrue($product->category->is_serialized);
    }

    // === Employee ===

    public function test_creates_employee(): void
    {
        $employee = Employee::query()->where('rpe', 'RPE-001')->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Juan Pérez García', $employee->name);
    }

    // === Assets (5 states) ===

    public function test_creates_asset_disponible(): void
    {
        $asset = Asset::query()->where('serial', 'SN-DEMO-001')->first();

        $this->assertNotNull($asset);
        $this->assertEquals(Asset::STATUS_AVAILABLE, $asset->status);
        $this->assertEquals('AT-001', $asset->asset_tag);
        $this->assertNull($asset->current_employee_id);
    }

    public function test_creates_asset_asignado_with_employee(): void
    {
        $asset = Asset::query()->where('serial', 'SN-DEMO-002')->first();

        $this->assertNotNull($asset);
        $this->assertEquals(Asset::STATUS_ASSIGNED, $asset->status);
        $this->assertNotNull($asset->current_employee_id);
    }

    public function test_creates_asset_prestado(): void
    {
        $asset = Asset::query()->where('serial', 'SN-DEMO-003')->first();

        $this->assertNotNull($asset);
        $this->assertEquals(Asset::STATUS_LOANED, $asset->status);
        $this->assertNotNull($asset->current_employee_id);
    }

    public function test_creates_asset_pendiente_de_retiro(): void
    {
        $asset = Asset::query()->where('serial', 'SN-DEMO-004')->first();

        $this->assertNotNull($asset);
        $this->assertEquals(Asset::STATUS_PENDING_RETIREMENT, $asset->status);
    }

    public function test_creates_asset_retirado(): void
    {
        $asset = Asset::query()->where('serial', 'SN-DEMO-005')->first();

        $this->assertNotNull($asset);
        $this->assertEquals(Asset::STATUS_RETIRED, $asset->status);
    }

    public function test_assets_have_unique_asset_tags(): void
    {
        $assetTags = Asset::query()->whereNotNull('asset_tag')->pluck('asset_tag');
        $uniqueTags = $assetTags->unique();

        $this->assertEquals($assetTags->count(), $uniqueTags->count());
    }

    public function test_assets_have_unique_product_serial_combination(): void
    {
        $product = Product::query()->where('name', 'Laptop Dell Latitude 5540')->first();
        $serials = Asset::query()
            ->where('product_id', $product->id)
            ->pluck('serial');
        $uniqueSerials = $serials->unique();

        $this->assertEquals($serials->count(), $uniqueSerials->count());
    }

    // === Pending Task ===

    public function test_creates_pending_task_in_ready_state(): void
    {
        $task = PendingTask::query()
            ->where('description', 'Tarea demo para pruebas de locks')
            ->first();

        $this->assertNotNull($task);
        $this->assertEquals(PendingTaskStatus::Ready, $task->status);
        $this->assertEquals(PendingTaskType::Assign, $task->type);
    }

    public function test_pending_task_has_lines(): void
    {
        $task = PendingTask::query()
            ->where('description', 'Tarea demo para pruebas de locks')
            ->first();

        $this->assertNotNull($task);
        $this->assertGreaterThanOrEqual(2, $task->lines()->count());
    }

    public function test_pending_task_has_no_lock_initially(): void
    {
        $task = PendingTask::query()
            ->where('description', 'Tarea demo para pruebas de locks')
            ->first();

        $this->assertNotNull($task);
        $this->assertNull($task->locked_by_user_id);
        $this->assertNull($task->expires_at);
    }
}
