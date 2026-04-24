<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

/**
 * @phpstan-type CriticalQueuePreviewItem array{
 *     priority: int,
 *     score: int,
 *     type: string,
 *     variant: string,
 *     icon: string,
 *     title: string,
 *     subtitle: string|null,
 *     detail: string|null,
 *     detailHint: string|null,
 *     location: string|null,
 *     actor: string|null,
 *     href: string|null
 * }
 * @phpstan-type CriticalQueuePreviewRow array{
 *     type: string,
 *     variant: string,
 *     icon: string,
 *     title: string,
 *     subtitle: string|null,
 *     detail: string|null,
 *     detailHint: string|null,
 *     location: string|null,
 *     actor: string|null,
 *     href: string|null
 * }
 */
final class CriticalQueuePreviewBuilder
{
    /**
     * @param  list<CriticalQueuePreviewItem>  $items
     * @return list<CriticalQueuePreviewRow>
     */
    public function build(array $items, int $limit): array
    {
        usort($items, static function (array $a, array $b): int {
            $priority = $b['priority'] <=> $a['priority'];
            if ($priority !== 0) {
                return $priority;
            }

            return $b['score'] <=> $a['score'];
        });

        $preview = [];

        foreach (array_slice($items, 0, $this->normalizeLimit($limit)) as $item) {
            unset($item['priority'], $item['score']);
            $preview[] = $item;
        }

        return $preview;
    }

    private function normalizeLimit(int $limit): int
    {
        return $limit > 0 ? $limit : 10;
    }
}
