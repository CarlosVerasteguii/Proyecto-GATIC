<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Models\PendingTaskLine;
use DomainException;

class PendingTaskEditableLineResolver
{
    public function resolveDraftEditorLine(int $pendingTaskId, ?int $lineId): ?PendingTaskLine
    {
        return $this->findOwnedLine($pendingTaskId, $lineId);
    }

    /**
     * @throws DomainException
     */
    public function resolveProcessEditorLine(int $pendingTaskId, ?int $lineId): ?PendingTaskLine
    {
        $line = $this->findOwnedLine($pendingTaskId, $lineId);

        if (! $line) {
            return null;
        }

        if ($line->line_status === PendingTaskLineStatus::Applied) {
            throw new DomainException('No se puede editar un renglón ya aplicado.');
        }

        return $line;
    }

    private function findOwnedLine(int $pendingTaskId, ?int $lineId): ?PendingTaskLine
    {
        $line = $this->findLine($lineId);

        if (! $line || $line->pending_task_id !== $pendingTaskId) {
            return null;
        }

        return $line;
    }

    protected function findLine(?int $lineId): ?PendingTaskLine
    {
        if (! is_int($lineId)) {
            return null;
        }

        return PendingTaskLine::find($lineId);
    }
}
