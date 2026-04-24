<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Actions\PendingTasks\UpdateTaskLine;
use App\Enums\PendingTaskLineStatus;
use App\Models\PendingTaskLine;
use Illuminate\Validation\ValidationException;

final class PendingTaskProcessLineSaver
{
    /**
     * @param  array{
     *     serial?: string,
     *     asset_tag?: string,
     *     quantity?: string|null,
     *     employee_id?: int|null,
     *     note?: string
     * }  $input
     *
     * @throws ValidationException
     */
    public function save(PendingTaskLine $line, array $input): PendingTaskLine
    {
        $payload = (new PendingTaskLinePayloadNormalizer)->normalize(
            $line->line_type->value,
            $line->product_id,
            $input,
        );

        (new UpdateTaskLine)->execute($line->id, $payload);

        $line->line_status = PendingTaskLineStatus::Pending;
        $line->error_message = null;
        $line->save();

        return $line->refresh();
    }
}
