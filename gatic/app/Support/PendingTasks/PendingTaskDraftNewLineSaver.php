<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Actions\PendingTasks\AddLineToTask;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

final class PendingTaskDraftNewLineSaver
{
    /**
     * @param  array{
     *     serial?: string,
     *     asset_tag?: string,
     *     quantity?: string|null,
     *     employee_id?: int|null,
     *     note?: string
     * }  $input
     * @return array{line: PendingTaskLine, has_duplicates: bool}
     *
     * @throws ValidationException
     */
    public function save(
        int $pendingTaskId,
        string $lineType,
        ?int $productId,
        array $input,
    ): array {
        $payload = (new PendingTaskLinePayloadNormalizer)->normalize(
            $lineType,
            $productId,
            $input,
        );

        return (new AddLineToTask)->execute([
            'pending_task_id' => $pendingTaskId,
            ...$payload,
        ]);
    }
}
