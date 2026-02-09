<?php

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

class MarkTaskAsReady
{
    /**
     * Transition a pending task from draft to ready status.
     */
    public function execute(int $taskId): PendingTask
    {
        $task = PendingTask::findOrFail($taskId);

        if ($task->isQuickCaptureTask()) {
            throw ValidationException::withMessages([
                'status' => ['Esta tarea fue creada como captura rápida y no se puede marcar como lista.'],
            ]);
        }

        // Task must be in draft status
        if ($task->status !== PendingTaskStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Solo las tareas en estado borrador pueden marcarse como listas.'],
            ]);
        }

        // Must have at least one pending line
        $pendingLinesCount = PendingTaskLine::where('pending_task_id', $task->id)
            ->where('line_status', PendingTaskLineStatus::Pending)
            ->count();

        if ($pendingLinesCount === 0) {
            throw ValidationException::withMessages([
                'lines' => ['La tarea debe tener al menos un renglón pendiente para marcarse como lista.'],
            ]);
        }

        // Update status to ready
        $task->status = PendingTaskStatus::Ready;
        $task->save();

        return $task;
    }
}
