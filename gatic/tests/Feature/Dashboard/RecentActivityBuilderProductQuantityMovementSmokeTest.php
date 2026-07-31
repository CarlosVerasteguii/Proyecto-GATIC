<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use App\Support\Dashboard\RecentActivityBuilder;
use App\Support\Timeline\TimelineEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecentActivityBuilderProductQuantityMovementSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_builds_recent_activity_items_for_product_quantity_movements(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 7, 12, 0, 0));

        $actor = User::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create([
            'rpe' => 'RPE-01',
            'name' => 'Ana Ruiz',
        ]);
        $product = Product::factory()->create(['name' => 'Mouse']);

        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'qty' => 5,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'qty' => 3,
            'created_at' => Carbon::now()->subMinutes(1),
        ]);

        $items = (new RecentActivityBuilder(
            canViewAttachments: true,
            canManageInventory: true,
        ))->build();

        $this->assertCount(2, $items);

        $this->assertSame(TimelineEventType::PRODUCT_QTY_OUT, $items[0]['type']);
        $this->assertSame('Salida', $items[0]['label']);
        $this->assertStringContainsString('Salida x3: Mouse', $items[0]['title']);
        $this->assertStringContainsString('Empleado: RPE-01 — Ana Ruiz', $items[0]['summary']);

        $this->assertSame(TimelineEventType::PRODUCT_QTY_IN, $items[1]['type']);
        $this->assertSame('Entrada', $items[1]['label']);
        $this->assertStringContainsString('Entrada x5: Mouse', $items[1]['title']);
        $this->assertStringContainsString('Empleado: RPE-01 — Ana Ruiz', $items[1]['summary']);
    }
}
