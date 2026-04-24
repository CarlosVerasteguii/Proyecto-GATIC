<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Asset;
use Carbon\CarbonInterface;

/**
 * @phpstan-type CriticalQueueAssetRow array{
 *     title: string,
 *     subtitle: string|null,
 *     detail: string|null,
 *     detailHint: string|null,
 *     location: string|null,
 *     actor: string|null,
 *     href: string
 * }
 */
final class CriticalQueueAssetRowMapper
{
    /**
     * @return CriticalQueueAssetRow
     */
    public function mapOverdueRow(
        Asset $asset,
        ?CarbonInterface $dueDate,
        ?int $overdueDays,
        string $href,
        ?string $actor = null,
    ): array {
        return [
            'title' => $this->title($asset),
            'subtitle' => $asset->product?->name,
            'detail' => $dueDate?->format('d/m/Y'),
            'detailHint' => $overdueDays !== null && $overdueDays > 0 ? "Hace {$overdueDays}d" : null,
            'location' => $asset->location?->name,
            'actor' => $actor,
            'href' => $href,
        ];
    }

    private function title(Asset $asset): string
    {
        $title = $asset->serial;

        if (! is_string($asset->asset_tag) || $asset->asset_tag === '') {
            return $title;
        }

        return "{$title} ({$asset->asset_tag})";
    }
}
