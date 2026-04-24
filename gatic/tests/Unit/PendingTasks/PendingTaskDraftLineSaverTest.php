<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Support\PendingTasks\PendingTaskDraftLineSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskDraftLineSaverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_a_serialized_draft_line(): void
    {
        $employee = Employee::factory()->create();
        $replacementEmployee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: true);
        $task = $this->createDraftTask();

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'SER-OLD',
            'asset_tag' => 'TAG-OLD',
            'note' => 'Nota anterior',
        ]);

        $result = (new PendingTaskDraftLineSaver)->save(
            $task->id,
            $line->id,
            PendingTaskLineType::Serialized->value,
            $product->id,
            [
                'serial' => 'SER-NEW',
                'asset_tag' => 'TAG-NEW',
                'employee_id' => $replacementEmployee->id,
                'note' => 'Nota nueva',
            ],
        );

        $this->assertNotNull($result);
        $this->assertFalse($result['has_duplicates']);
        $this->assertSame('SER-NEW', $result['line']->serial);
        $this->assertSame('TAG-NEW', $result['line']->asset_tag);
        $this->assertSame($replacementEmployee->id, $result['line']->employee_id);
        $this->assertSame('Nota nueva', $result['line']->note);
    }

    #[Test]
    public function it_updates_a_quantity_draft_line(): void
    {
        $employee = Employee::factory()->create();
        $replacementEmployee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: false);
        $task = $this->createDraftTask();

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Quantity,
            'quantity' => 2,
            'serial' => null,
            'asset_tag' => null,
            'note' => 'Nota anterior',
        ]);

        $result = (new PendingTaskDraftLineSaver)->save(
            $task->id,
            $line->id,
            PendingTaskLineType::Quantity->value,
            $product->id,
            [
                'quantity' => '7',
                'employee_id' => $replacementEmployee->id,
                'note' => 'Nota cantidad',
            ],
        );

        $this->assertNotNull($result);
        $this->assertFalse($result['has_duplicates']);
        $this->assertSame(7, $result['line']->quantity);
        $this->assertNull($result['line']->serial);
        $this->assertNull($result['line']->asset_tag);
        $this->assertSame($replacementEmployee->id, $result['line']->employee_id);
        $this->assertSame('Nota cantidad', $result['line']->note);
    }

    #[Test]
    public function it_returns_null_when_the_line_does_not_belong_to_the_task(): void
    {
        $employee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: true);
        $ownerTask = $this->createDraftTask();
        $otherTask = $this->createDraftTask();

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $ownerTask->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'SER-OLD',
            'asset_tag' => 'TAG-OLD',
            'note' => 'Nota anterior',
        ]);

        $result = (new PendingTaskDraftLineSaver)->save(
            $otherTask->id,
            $line->id,
            PendingTaskLineType::Serialized->value,
            $product->id,
            [
                'serial' => 'SER-NEW',
                'asset_tag' => 'TAG-NEW',
                'employee_id' => $employee->id,
                'note' => 'Nota nueva',
            ],
        );

        $this->assertNull($result);

        $line->refresh();

        $this->assertSame('SER-OLD', $line->serial);
        $this->assertSame('TAG-OLD', $line->asset_tag);
        $this->assertSame('Nota anterior', $line->note);
    }

    #[Test]
    public function it_preserves_duplicate_detection_from_the_update_action(): void
    {
        $employee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: true);
        $task = $this->createDraftTask();

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'SER-DUP',
            'asset_tag' => 'TAG-DUP',
            'note' => 'Duplicado existente',
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

        $result = (new PendingTaskDraftLineSaver)->save(
            $task->id,
            $line->id,
            PendingTaskLineType::Serialized->value,
            $product->id,
            [
                'serial' => 'SER-DUP',
                'asset_tag' => 'TAG-NEW',
                'employee_id' => $employee->id,
                'note' => 'Nota nueva',
            ],
        );

        $this->assertNotNull($result);
        $this->assertTrue($result['has_duplicates']);
    }

    private function createDraftTask(): PendingTask
    {
        return PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Draft,
        ]);
    }

    private function createProduct(bool $isSerialized): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory()->create(['is_serialized' => $isSerialized])->id,
        ]);
    }
}
