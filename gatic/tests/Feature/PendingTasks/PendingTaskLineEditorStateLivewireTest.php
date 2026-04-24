<?php

declare(strict_types=1);

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingTaskLineEditorStateLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_line_modal_keeps_hydration_and_reset_behavior(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'SER-100',
            'asset_tag' => 'TAG-100',
            'quantity' => null,
            'note' => 'Nota original',
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->set('lineType', PendingTaskLineType::Quantity->value)
            ->set('productId', 999)
            ->set('serial', 'STALE-SERIAL')
            ->set('assetTag', 'STALE-TAG')
            ->set('quantity', '8')
            ->set('serializedBulkInput', "A\nB")
            ->set('serializedBulkPreview', [
                ['line' => 1, 'value' => 'A', 'status' => 'ok', 'status_label' => 'OK', 'message' => null],
            ])
            ->set('serializedBulkCount', 2)
            ->set('serializedBulkOkCount', 1)
            ->set('serializedBulkDuplicateCount', 1)
            ->set('serializedBulkInvalidCount', 0)
            ->set('serializedBulkLimitError', 'Límite')
            ->set('employeeId', $employee->id)
            ->set('note', 'Nota vieja')
            ->set('editingLineId', 123)
            ->call('openAddLineModal')
            ->assertSet('showLineModal', true)
            ->assertSet('lineType', '')
            ->assertSet('productId', null)
            ->assertSet('serial', '')
            ->assertSet('assetTag', '')
            ->assertSet('quantity', null)
            ->assertSet('serializedBulkInput', '')
            ->assertSet('serializedBulkPreview', [])
            ->assertSet('serializedBulkCount', 0)
            ->assertSet('serializedBulkOkCount', 0)
            ->assertSet('serializedBulkDuplicateCount', 0)
            ->assertSet('serializedBulkInvalidCount', 0)
            ->assertSet('serializedBulkLimitError', null)
            ->assertSet('employeeId', null)
            ->assertSet('note', '')
            ->assertSet('editingLineId', null)
            ->call('openEditLineModal', $line->id)
            ->assertSet('editingLineId', $line->id)
            ->assertSet('lineType', PendingTaskLineType::Serialized->value)
            ->assertSet('productId', $product->id)
            ->assertSet('serial', 'SER-100')
            ->assertSet('assetTag', 'TAG-100')
            ->assertSet('quantity', null)
            ->assertSet('employeeId', $employee->id)
            ->assertSet('note', 'Nota original')
            ->call('closeModal')
            ->assertSet('showLineModal', false)
            ->assertSet('lineType', '')
            ->assertSet('productId', null)
            ->assertSet('serial', '')
            ->assertSet('assetTag', '')
            ->assertSet('quantity', null)
            ->assertSet('serializedBulkInput', '')
            ->assertSet('serializedBulkPreview', [])
            ->assertSet('serializedBulkCount', 0)
            ->assertSet('serializedBulkOkCount', 0)
            ->assertSet('serializedBulkDuplicateCount', 0)
            ->assertSet('serializedBulkInvalidCount', 0)
            ->assertSet('serializedBulkLimitError', null)
            ->assertSet('employeeId', null)
            ->assertSet('note', '')
            ->assertSet('editingLineId', null);
    }

    public function test_process_line_modal_keeps_hydration_and_reset_behavior(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => false]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $editor->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Quantity,
            'quantity' => 4,
            'serial' => null,
            'asset_tag' => null,
            'note' => 'Nota de proceso',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('enterProcessMode')
            ->set('editingProcessLineId', 99)
            ->set('processLineSerial', 'STALE')
            ->set('processLineAssetTag', 'STALE-TAG')
            ->set('processLineQuantity', '9')
            ->set('processLineEmployeeId', 55)
            ->set('processLineNote', 'Vieja')
            ->call('openProcessLineModal', $line->id)
            ->assertSet('showProcessLineModal', true)
            ->assertSet('editingProcessLineId', $line->id)
            ->assertSet('processLineSerial', '')
            ->assertSet('processLineAssetTag', '')
            ->assertSet('processLineQuantity', '4')
            ->assertSet('processLineEmployeeId', $employee->id)
            ->assertSet('processLineNote', 'Nota de proceso')
            ->call('closeProcessLineModal')
            ->assertSet('showProcessLineModal', false)
            ->assertSet('editingProcessLineId', null)
            ->assertSet('processLineSerial', '')
            ->assertSet('processLineAssetTag', '')
            ->assertSet('processLineQuantity', null)
            ->assertSet('processLineEmployeeId', null)
            ->assertSet('processLineNote', '');
    }

    public function test_process_line_modal_blocks_applied_lines_from_the_same_task(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
            'locked_by_user_id' => $editor->id,
            'locked_at' => now(),
            'heartbeat_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'SER-APPLIED',
            'asset_tag' => 'TAG-APPLIED',
            'line_status' => PendingTaskLineStatus::Applied,
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openProcessLineModal', $line->id)
            ->assertSet('showProcessLineModal', false)
            ->assertSet('editingProcessLineId', null)
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['type'] ?? null) === 'error'
                    && ($params['message'] ?? null) === 'No se puede editar un renglón ya aplicado.';
            });
    }
}
