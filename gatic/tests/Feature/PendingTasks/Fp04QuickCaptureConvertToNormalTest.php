<?php

namespace Tests\Feature\PendingTasks;

use App\Actions\PendingTasks\AcquirePendingTaskLock;
use App\Actions\PendingTasks\AddLineToTask;
use App\Actions\PendingTasks\FinalizePendingTask;
use App\Actions\PendingTasks\MarkTaskAsReady;
use App\Actions\PendingTasks\ProcessQuickCapturePendingTask;
use App\Actions\PendingTasks\RemoveLineFromTask;
use App\Actions\PendingTasks\UpdateTaskLine;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class Fp04QuickCaptureConvertToNormalTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_capture_pending_remains_blocked_for_normal_actions(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id, 'qty_total' => 10]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
                'product' => [
                    'mode' => 'existing',
                    'id' => $product->id,
                    'name' => $product->name,
                    'is_serialized' => false,
                ],
                'items' => [
                    'type' => 'quantity',
                    'quantity' => 1,
                ],
                'note' => 'Captura rápida pendiente',
            ],
        ]);

        try {
            (new AddLineToTask)->execute([
                'pending_task_id' => $task->id,
                'line_type' => PendingTaskLineType::Quantity->value,
                'product_id' => $product->id,
                'quantity' => 1,
                'employee_id' => $employee->id,
                'note' => 'No debe permitir',
            ]);
            $this->fail('Expected ValidationException for AddLineToTask on quick capture pending task.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Esta tarea fue creada como captura rápida y no permite añadir renglones.',
                $e->errors()['pending_task_id'][0] ?? null
            );
        }

        try {
            (new MarkTaskAsReady)->execute($task->id);
            $this->fail('Expected ValidationException for MarkTaskAsReady on quick capture pending task.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Esta tarea fue creada como captura rápida y no se puede marcar como lista.',
                $e->errors()['status'][0] ?? null
            );
        }

        try {
            (new AcquirePendingTaskLock)->execute($task->id, $editor->id);
            $this->fail('Expected ValidationException for AcquirePendingTaskLock on quick capture pending task.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Esta tarea fue creada como captura rápida y no se puede procesar en esta versión.',
                $e->errors()['status'][0] ?? null
            );
        }

        try {
            (new FinalizePendingTask)->execute($task->id, $editor->id);
            $this->fail('Expected ValidationException for FinalizePendingTask on quick capture pending task.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Esta tarea fue creada como captura rápida y no se puede finalizar en esta versión.',
                $e->errors()['status'][0] ?? null
            );
        }
    }

    public function test_converted_quick_capture_allows_line_crud_ready_lock_and_finalize(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id, 'qty_total' => 10]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
                'product' => [
                    'mode' => 'existing',
                    'id' => $product->id,
                    'name' => $product->name,
                    'is_serialized' => false,
                ],
                'items' => [
                    'type' => 'quantity',
                    'quantity' => 3,
                ],
                'note' => 'Captura rápida a convertir',
            ],
        ]);

        $result = (new ProcessQuickCapturePendingTask)->execute([
            'task_id' => $task->id,
            'actor_user_id' => $editor->id,
            'employee_id' => $employee->id,
            'note' => 'Convertido desde test',
        ]);
        $this->assertSame('lines', $result['mode']);

        $task->refresh();
        $this->assertTrue($task->hasQuickCapturePayload());
        $this->assertFalse($task->isQuickCaptureTask());
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);

        $addResult = (new AddLineToTask)->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity->value,
            'product_id' => $product->id,
            'quantity' => 2,
            'employee_id' => $employee->id,
            'note' => 'Línea extra',
        ]);

        $this->assertEquals(2, $task->lines()->count());

        $updated = (new UpdateTaskLine)->execute($addResult['line']->id, [
            'quantity' => 5,
            'note' => 'Línea actualizada',
        ]);

        $this->assertSame('Línea actualizada', $updated['line']->note);
        $this->assertSame(5, $updated['line']->quantity);

        (new RemoveLineFromTask)->execute($updated['line']->id);
        $this->assertEquals(1, $task->lines()->count());

        (new MarkTaskAsReady)->execute($task->id);
        $task->refresh();
        $this->assertEquals(PendingTaskStatus::Ready, $task->status);

        $lock = (new AcquirePendingTaskLock)->execute($task->id, $editor->id);
        $this->assertTrue($lock['success']);

        $finalize = (new FinalizePendingTask)->execute($task->id, $editor->id);
        $this->assertGreaterThan(0, $finalize['applied_count']);
        $this->assertEquals(0, $finalize['error_count']);
        $this->assertEquals(PendingTaskStatus::Completed, $finalize['task_status']);
    }

    public function test_process_quick_capture_livewire_is_blocked_when_already_converted(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id, 'qty_total' => 10]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'payload' => [
                'schema' => 'fp03.quick_capture',
                'version' => 1,
                'kind' => 'quick_stock_in',
                'product' => [
                    'mode' => 'existing',
                    'id' => $product->id,
                    'name' => $product->name,
                    'is_serialized' => false,
                ],
                'items' => [
                    'type' => 'quantity',
                    'quantity' => 1,
                ],
                'note' => 'Ya convertida',
            ],
        ]);

        (new ProcessQuickCapturePendingTask)->execute([
            'task_id' => $task->id,
            'actor_user_id' => $editor->id,
            'employee_id' => $employee->id,
            'note' => 'Convertida',
        ]);

        $task->refresh();
        $this->assertFalse($task->isQuickCaptureTask());

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('processQuickCapture')
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['type'] ?? null) === 'error'
                    && ($params['message'] ?? null) === 'Esta captura rápida ya fue procesada y no puede procesarse de nuevo.';
            });

        $this->assertEquals(1, $task->lines()->count());
    }
}
