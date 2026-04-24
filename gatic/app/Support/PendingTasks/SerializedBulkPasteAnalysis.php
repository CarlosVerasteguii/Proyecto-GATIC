<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

/**
 * @phpstan-type SerializedBulkPreviewItem array{
 *     line: int,
 *     value: string,
 *     status: string,
 *     status_label: string,
 *     message: string|null
 * }
 */
final readonly class SerializedBulkPasteAnalysis
{
    /**
     * @param  list<SerializedBulkPreviewItem>  $preview
     * @param  list<string>  $serials
     */
    public function __construct(
        public array $preview,
        public array $serials,
        public int $count,
        public int $okCount,
        public int $duplicateCount,
        public int $invalidCount,
        public ?string $limitError,
    ) {}
}
