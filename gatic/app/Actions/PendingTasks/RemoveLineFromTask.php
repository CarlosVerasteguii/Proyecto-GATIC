<?php

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

class RemoveLineFromTask
{
    /**
     * Remove a line from a pending task (physical delete).
     */
    public function execute(int $lineId): void
    {
        $line = PendingTaskLine::with('pendingTask')->findOrFail($lineId);
        $task = $line->pendingTask;

        // Task must be in draft status
        if ($task->status !== PendingTaskStatus::Draft) {
            throw ValidationException::withMessages([
                'pending_task_id' => ['La tarea no está en estado borrador. No se pueden eliminar renglones.'],
            ]);
        }

        // Physical delete
        $line->delete();
    }
}
