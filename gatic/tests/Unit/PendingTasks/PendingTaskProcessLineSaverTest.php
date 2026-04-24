<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Support\PendingTasks\PendingTaskProcessLineSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskProcessLineSaverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_a_serialized_line_and_clears_its_error_state(): void
    {
        $employee = Employee::factory()->create();
        $replacementEmployee = Employee::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create(['is_serialized' => true])->id,
        ]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
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

        $savedLine = (new PendingTaskProcessLineSaver)->save($line, [
            'serial' => 'SER-NEW',
            'asset_tag' => 'TAG-NEW',
            'employee_id' => $replacementEmployee->id,
            'note' => 'Nota nueva',
        ]);

        $this->assertSame('SER-NEW', $savedLine->serial);
        $this->assertSame('TAG-NEW', $savedLine->asset_tag);
        $this->assertSame($replacementEmployee->id, $savedLine->employee_id);
        $this->assertSame('Nota nueva', $savedLine->note);
        $this->assertSame(PendingTaskLineStatus::Pending, $savedLine->line_status);
        $this->assertNull($savedLine->error_message);
    }

    #[Test]
    public function it_updates_a_quantity_line_and_clears_its_error_state(): void
    {
        $employee = Employee::factory()->create();
        $replacementEmployee = Employee::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create(['is_serialized' => false])->id,
        ]);

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Quantity,
            'quantity' => 2,
            'serial' => null,
            'asset_tag' => null,
            'note' => 'Nota anterior',
            'line_status' => PendingTaskLineStatus::Error,
            'error_message' => 'Error anterior',
        ]);

        $savedLine = (new PendingTaskProcessLineSaver)->save($line, [
            'quantity' => '7',
            'employee_id' => $replacementEmployee->id,
            'note' => 'Nota cantidad',
        ]);

        $this->assertSame(7, $savedLine->quantity);
        $this->assertNull($savedLine->serial);
        $this->assertNull($savedLine->asset_tag);
        $this->assertSame($replacementEmployee->id, $savedLine->employee_id);
        $this->assertSame('Nota cantidad', $savedLine->note);
        $this->assertSame(PendingTaskLineStatus::Pending, $savedLine->line_status);
        $this->assertNull($savedLine->error_message);
    }
}
