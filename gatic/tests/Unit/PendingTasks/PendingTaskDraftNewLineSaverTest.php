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
use App\Support\PendingTasks\PendingTaskDraftNewLineSaver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskDraftNewLineSaverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_quantity_line_to_a_draft_task(): void
    {
        $employee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: false);
        $task = $this->createDraftTask();

        $result = (new PendingTaskDraftNewLineSaver)->save(
            $task->id,
            PendingTaskLineType::Quantity->value,
            $product->id,
            [
                'quantity' => '7',
                'employee_id' => $employee->id,
                'note' => 'Nueva línea por cantidad',
            ],
        );

        $this->assertFalse($result['has_duplicates']);
        $this->assertSame($task->id, $result['line']->pending_task_id);
        $this->assertSame(PendingTaskLineType::Quantity, $result['line']->line_type);
        $this->assertSame($product->id, $result['line']->product_id);
        $this->assertSame(7, $result['line']->quantity);
        $this->assertNull($result['line']->serial);
        $this->assertNull($result['line']->asset_tag);
        $this->assertSame($employee->id, $result['line']->employee_id);
        $this->assertSame('Nueva línea por cantidad', $result['line']->note);
    }

    #[Test]
    public function it_preserves_duplicate_detection_when_adding_a_serialized_line(): void
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
            'asset_tag' => 'TAG-001',
            'note' => 'Existente',
        ]);

        $result = (new PendingTaskDraftNewLineSaver)->save(
            $task->id,
            PendingTaskLineType::Serialized->value,
            $product->id,
            [
                'serial' => 'SER-DUP',
                'asset_tag' => 'TAG-002',
                'employee_id' => $employee->id,
                'note' => 'Nueva línea serializada',
            ],
        );

        $this->assertTrue($result['has_duplicates']);
        $this->assertSame('SER-DUP', $result['line']->serial);
        $this->assertSame('TAG-002', $result['line']->asset_tag);
    }

    #[Test]
    public function it_throws_when_quantity_is_not_a_positive_integer(): void
    {
        $employee = Employee::factory()->create();
        $product = $this->createProduct(isSerialized: false);
        $task = $this->createDraftTask();

        $this->expectException(ValidationException::class);

        (new PendingTaskDraftNewLineSaver)->save(
            $task->id,
            PendingTaskLineType::Quantity->value,
            $product->id,
            [
                'quantity' => '0',
                'employee_id' => $employee->id,
                'note' => 'Inválida',
            ],
        );
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
