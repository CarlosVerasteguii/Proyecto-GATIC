<?php

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

class PendingTaskProcessLineSaveLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_process_line_updates_the_line_and_clears_error_state(): void
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
            'serial' => 'SER-OLD',
            'asset_tag' => 'TAG-OLD',
            'note' => 'Nota anterior',
            'line_status' => PendingTaskLineStatus::Error,
            'error_message' => 'Error anterior',
        ]);

        Livewire::actingAs($editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->set('isProcessMode', true)
            ->call('openProcessLineModal', $line->id)
            ->assertSet('showProcessLineModal', true)
            ->set('processLineSerial', 'SER-NEW')
            ->set('processLineAssetTag', 'TAG-NEW')
            ->set('processLineEmployeeId', $replacementEmployee->id)
            ->set('processLineNote', 'Nota actualizada')
            ->call('saveProcessLine')
            ->assertHasNoErrors()
            ->assertSet('showProcessLineModal', false)
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['type'] ?? null) === 'success'
                    && ($params['message'] ?? null) === 'Renglón actualizado. Re-valida para verificar.';
            });

        $line->refresh();

        $this->assertSame('SER-NEW', $line->serial);
        $this->assertSame('TAG-NEW', $line->asset_tag);
        $this->assertSame($replacementEmployee->id, $line->employee_id);
        $this->assertSame('Nota actualizada', $line->note);
        $this->assertSame(PendingTaskLineStatus::Pending, $line->line_status);
        $this->assertNull($line->error_message);
    }
}
