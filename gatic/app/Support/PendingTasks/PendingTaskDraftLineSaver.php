<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Actions\PendingTasks\UpdateTaskLine;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

final class PendingTaskDraftLineSaver
{
    /**
     * @param  array{
     *     serial?: string,
     *     asset_tag?: string,
     *     quantity?: string|null,
     *     employee_id?: int|null,
     *     note?: string
     * }  $input
     * @return array{line: PendingTaskLine, has_duplicates: bool}|null
     *
     * @throws ValidationException
     */
    public function save(
        int $pendingTaskId,
        ?int $lineId,
        string $lineType,
        ?int $productId,
        array $input,
    ): ?array {
        $payload = (new PendingTaskLinePayloadNormalizer)->normalize(
            $lineType,
            $productId,
            $input,
        );

        $line = (new PendingTaskEditableLineResolver)->resolveDraftEditorLine($pendingTaskId, $lineId);

        if (! $line) {
            return null;
        }

        return (new UpdateTaskLine)->execute($line->id, $payload);
    }
}
