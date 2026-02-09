<?php

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Livewire\PendingTasks\QuickRetirement;
use App\Livewire\PendingTasks\QuickStockIn;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Fp03QuickCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_cannot_use_quick_capture_actions(): void
    {
        $reader = User::factory()->create(['role' => 'Lector']);

        Livewire::actingAs($reader)
            ->test(QuickStockIn::class)
            ->assertForbidden();

        Livewire::actingAs($reader)
            ->test(QuickRetirement::class)
            ->assertForbidden();
    }

    public function test_quick_stock_in_serialized_creates_draft_pending_task_with_expected_payload(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Laptop Demo']);

        Livewire::actingAs($editor)
            ->test(QuickStockIn::class)
            ->set('productMode', 'existing')
            ->set('productId', $product->id)
            ->set('serialsInput', "ABC123\nABC124")
            ->set('note', 'Urgente')
            ->call('save');

        $task = PendingTask::query()->latest('id')->first();

        $this->assertNotNull($task);
        $this->assertEquals(PendingTaskType::StockIn, $task->type);
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);
        $this->assertEquals($editor->id, $task->creator_user_id);

        $this->assertIsArray($task->payload);
        $this->assertSame('fp03.quick_capture', $task->payload['schema'] ?? null);
        $this->assertSame(1, $task->payload['version'] ?? null);
        $this->assertSame('quick_stock_in', $task->payload['kind'] ?? null);

        $productPayload = $task->payload['product'] ?? null;
        $this->assertIsArray($productPayload);
        $this->assertSame('existing', $productPayload['mode'] ?? null);
        $this->assertSame($product->id, $productPayload['id'] ?? null);
        $this->assertSame('Laptop Demo', $productPayload['name'] ?? null);
        $this->assertSame(true, $productPayload['is_serialized'] ?? null);

        $this->assertSame('serialized', $task->payload['items']['type'] ?? null);
        $this->assertSame(['ABC123', 'ABC124'], $task->payload['items']['serials'] ?? null);
        $this->assertSame('Urgente', $task->payload['note'] ?? null);
    }

    public function test_quick_stock_in_serials_rejects_duplicates(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs($editor)
            ->test(QuickStockIn::class)
            ->set('productMode', 'existing')
            ->set('productId', $product->id)
            ->set('serialsInput', "DUP001\nDUP001")
            ->call('save')
            ->assertHasErrors(['serialsInput']);

        $this->assertDatabaseCount('pending_tasks', 0);
    }

    public function test_quick_stock_in_serials_enforces_max_lines_from_config(): void
    {
        config(['gatic.pending_tasks.bulk_paste.max_lines' => 1]);

        $editor = User::factory()->create(['role' => 'Editor']);
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs($editor)
            ->test(QuickStockIn::class)
            ->set('productMode', 'existing')
            ->set('productId', $product->id)
            ->set('serialsInput', "A1\nA2")
            ->call('save')
            ->assertHasErrors(['serialsInput']);

        $this->assertDatabaseCount('pending_tasks', 0);
    }

    public function test_quick_stock_in_quantity_requires_min_one(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs($editor)
            ->test(QuickStockIn::class)
            ->set('productMode', 'existing')
            ->set('productId', $product->id)
            ->set('quantity', '0')
            ->call('save')
            ->assertHasErrors(['quantity']);

        $this->assertDatabaseCount('pending_tasks', 0);

        Livewire::actingAs($editor)
            ->test(QuickStockIn::class)
            ->set('productMode', 'existing')
            ->set('productId', $product->id)
            ->set('quantity', '1')
            ->call('save');

        $this->assertDatabaseCount('pending_tasks', 1);
        $task = PendingTask::query()->first();

        $this->assertNotNull($task);
        $this->assertEquals(PendingTaskType::StockIn, $task->type);
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);
        $this->assertSame('quantity', $task->payload['items']['type'] ?? null);
        $this->assertSame(1, $task->payload['items']['quantity'] ?? null);
    }

    public function test_quick_retirement_requires_reason_and_persists_it_in_payload(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);

        Livewire::actingAs($editor)
            ->test(QuickRetirement::class)
            ->set('mode', 'serials')
            ->set('serialsInput', 'RET001')
            ->set('reason', '')
            ->call('save')
            ->assertHasErrors(['reason']);

        $this->assertDatabaseCount('pending_tasks', 0);

        Livewire::actingAs($editor)
            ->test(QuickRetirement::class)
            ->set('mode', 'serials')
            ->set('serialsInput', "RET001\nRET002")
            ->set('reason', 'Equipo dañado')
            ->set('note', 'Se retira hoy')
            ->call('save');

        $this->assertDatabaseCount('pending_tasks', 1);

        $task = PendingTask::query()->first();
        $this->assertNotNull($task);
        $this->assertEquals(PendingTaskType::Retirement, $task->type);
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);

        $this->assertIsArray($task->payload);
        $this->assertSame('fp03.quick_capture', $task->payload['schema'] ?? null);
        $this->assertSame(1, $task->payload['version'] ?? null);
        $this->assertSame('quick_retirement', $task->payload['kind'] ?? null);
        $this->assertNull($task->payload['product'] ?? null);
        $this->assertSame('serialized', $task->payload['items']['type'] ?? null);
        $this->assertSame(['RET001', 'RET002'], $task->payload['items']['serials'] ?? null);
        $this->assertSame('Equipo dañado', $task->payload['reason'] ?? null);
        $this->assertSame('Se retira hoy', $task->payload['note'] ?? null);
    }

    public function test_pending_task_show_blocks_edit_and_process_actions_for_quick_capture_tasks(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
                'product' => [
                    'mode' => 'placeholder',
                    'id' => null,
                    'name' => 'Producto Placeholder',
                    'is_serialized' => true,
                ],
                'items' => [
                    'type' => 'serialized',
                    'serials' => ['AAA001'],
                ],
                'note' => null,
            ],
        ]);

        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $product->id,
            'serial' => null,
            'asset_tag' => null,
            'quantity' => 1,
            'employee_id' => $employee->id,
        ]);

        $originalLinesCount = PendingTaskLine::query()->where('pending_task_id', $task->id)->count();

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('saveLine')
            ->call('removeLine', $line->id)
            ->call('markAsReady');

        $this->assertDatabaseCount('pending_task_lines', $originalLinesCount);

        $task->refresh();
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);

        $readyTask = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $editor->id,
            'locked_by_user_id' => null,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
                'product' => [
                    'mode' => 'placeholder',
                    'id' => null,
                    'name' => 'Producto Placeholder',
                    'is_serialized' => true,
                ],
                'items' => [
                    'type' => 'serialized',
                    'serials' => ['BBB001'],
                ],
                'note' => null,
            ],
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $readyTask->id])
            ->call('enterProcessMode')
            ->assertSet('isProcessMode', false)
            ->assertSet('hasLock', false);

        $readyTask->refresh();
        $this->assertEquals(PendingTaskStatus::Ready, $readyTask->status);
        $this->assertNull($readyTask->locked_by_user_id);
    }

    public function test_legacy_pending_task_routes_return_forbidden_for_quick_capture(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);

        $quickTask = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
            ],
        ]);

        $normalTask = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => null,
        ]);

        $this->actingAs($editor)
            ->get(route('pending-tasks.show', $quickTask).'/edit')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('pending-tasks.show', $quickTask).'/process')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('pending-tasks.show', $quickTask).'/lines')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('pending-tasks.show', $quickTask).'/lines/create')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('pending-tasks.show', $normalTask).'/edit')
            ->assertRedirect(route('pending-tasks.show', $normalTask));
    }
}
