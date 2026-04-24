<?php

namespace Tests\Feature\PendingTasks;

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

class PendingTaskDraftLineSaveLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_line_adds_a_new_quantity_draft_line(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create(['is_serialized' => false])->id,
        ]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $editor->id,
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->assertSet('showLineModal', true)
            ->set('productId', $product->id)
            ->assertSet('lineType', PendingTaskLineType::Quantity->value)
            ->set('quantity', '4')
            ->set('employeeId', $employee->id)
            ->set('note', 'Nueva línea desde Livewire')
            ->call('saveLine')
            ->assertHasNoErrors()
            ->assertSet('showLineModal', false)
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['type'] ?? null) === 'success'
                    && ($params['message'] ?? null) === 'Renglón añadido.';
            });

        $this->assertDatabaseHas('pending_task_lines', [
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'line_type' => PendingTaskLineType::Quantity->value,
            'quantity' => 4,
            'employee_id' => $employee->id,
            'note' => 'Nueva línea desde Livewire',
        ]);
    }

    public function test_save_line_updates_an_existing_draft_line(): void
    {
        $editor = User::factory()->create(['role' => 'Editor']);
        $employee = Employee::factory()->create();
        $replacementEmployee = Employee::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create(['is_serialized' => true])->id,
        ]);

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
            'serial' => 'SER-OLD',
            'asset_tag' => 'TAG-OLD',
            'note' => 'Nota anterior',
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openEditLineModal', $line->id)
            ->assertSet('showLineModal', true)
            ->set('serial', 'SER-NEW')
            ->set('assetTag', 'TAG-NEW')
            ->set('employeeId', $replacementEmployee->id)
            ->set('note', 'Nota actualizada')
            ->call('saveLine')
            ->assertHasNoErrors()
            ->assertSet('showLineModal', false)
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['type'] ?? null) === 'success'
                    && ($params['message'] ?? null) === 'Renglón actualizado.';
            });

        $line->refresh();

        $this->assertSame('SER-NEW', $line->serial);
        $this->assertSame('TAG-NEW', $line->asset_tag);
        $this->assertSame($replacementEmployee->id, $line->employee_id);
        $this->assertSame('Nota actualizada', $line->note);
    }
}
