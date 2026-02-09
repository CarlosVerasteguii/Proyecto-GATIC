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

        if ($task->isQuickCaptureTask()) {
            throw ValidationException::withMessages([
                'pending_task_id' => ['Esta tarea fue creada como captura rápida y no permite eliminar renglones.'],
            ]);
        }

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
