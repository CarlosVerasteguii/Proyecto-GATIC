<?php

declare(strict_types=1);

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

/**
 * Clear the error status from a PendingTaskLine, resetting it to pending.
 */
class ClearLineError
{
    /**
     * @param  int  $lineId  The PendingTaskLine ID
     * @return PendingTaskLine The updated line
     *
     * @throws ValidationException
     */
    public function execute(int $lineId): PendingTaskLine
    {
        $line = PendingTaskLine::findOrFail($lineId);

        if ($line->line_status === PendingTaskLineStatus::Applied) {
            throw ValidationException::withMessages([
                'line_status' => 'No se puede limpiar el error de un renglón ya aplicado.',
            ]);
        }

        $line->line_status = PendingTaskLineStatus::Pending;
        $line->error_message = null;
        $line->save();

        return $line;
    }
}
