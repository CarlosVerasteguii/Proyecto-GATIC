<?php

namespace Database\Seeders;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo pending task data for QA/dev testing.
 *
 * Creates a task in Ready state with lines for testing:
 * - Lock acquisition/release
 * - Concurrency (2 editors trying to process)
 * - Heartbeat/TTL expiration
 * - Admin override
 */
class DemoPendingTaskSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create an editor user as creator
        $editor = User::query()->where('email', 'editor@gatic.local')->first();
        if (! $editor) {
            $editor = User::factory()->create([
                'email' => 'editor@gatic.local',
                'name' => 'Editor User',
                'role' => UserRole::Editor,
            ]);
        }

        // Get existing demo data (created by DemoInventorySeeder)
        $product = Product::query()->where('name', 'Laptop Dell Latitude 5540')->first();
        $employee = Employee::query()->where('rpe', 'RPE-001')->first();
        $category = Category::query()->where('name', 'Equipo de Cómputo')->first();

        // If demo inventory doesn't exist, create minimal data for the task
        if (! $product || ! $employee || ! $category) {
            $category = Category::query()->firstOrCreate(
                ['name' => 'Equipo de Cómputo'],
                ['is_serialized' => true, 'requires_asset_tag' => true]
            );
            $product = Product::query()->firstOrCreate(
                ['name' => 'Laptop Dell Latitude 5540'],
                ['category_id' => $category->id]
            );
            $employee = Employee::query()->firstOrCreate(
                ['rpe' => 'RPE-001'],
                ['name' => 'Juan Pérez García', 'department' => 'TI']
            );
        }

        // === Pending Task in Ready state ===
        $task = PendingTask::query()->updateOrCreate(
            ['description' => 'Tarea demo para pruebas de locks'],
            [
                'type' => PendingTaskType::Assign,
                'status' => PendingTaskStatus::Ready,
                'creator_user_id' => $editor->id,
                'locked_by_user_id' => null,
                'locked_at' => null,
                'heartbeat_at' => null,
                'expires_at' => null,
            ]
        );

        // === Task Lines (2 serialized lines) ===
        PendingTaskLine::query()->updateOrCreate(
            [
                'pending_task_id' => $task->id,
                'serial' => 'TASK-SN-001',
            ],
            [
                'line_type' => PendingTaskLineType::Serialized,
                'product_id' => $product->id,
                'asset_tag' => 'TASK-AT-001',
                'quantity' => null,
                'employee_id' => $employee->id,
                'note' => 'Asignación de laptop a Juan Pérez',
                'line_status' => PendingTaskLineStatus::Pending,
                'order' => 1,
            ]
        );

        PendingTaskLine::query()->updateOrCreate(
            [
                'pending_task_id' => $task->id,
                'serial' => 'TASK-SN-002',
            ],
            [
                'line_type' => PendingTaskLineType::Serialized,
                'product_id' => $product->id,
                'asset_tag' => 'TASK-AT-002',
                'quantity' => null,
                'employee_id' => $employee->id,
                'note' => 'Asignación de laptop secundaria',
                'line_status' => PendingTaskLineStatus::Pending,
                'order' => 2,
            ]
        );
    }
}
