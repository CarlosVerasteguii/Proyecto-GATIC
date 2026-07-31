<?php

namespace App\Support\Timeline;

use App\Models\AssetMovement;

/**
 * Shared mapping between domain AssetMovement types and timeline event types.
 *
 * This exists to avoid drift between per-entity timeline and dashboard recent activity feeds.
 */
final class AssetMovementEventTypeMap
{
    /**
     * @return array<string, string> key: AssetMovement::TYPE_*, value: TimelineEventType::*
     */
    public static function map(): array
    {
        return [
            AssetMovement::TYPE_ASSIGN => TimelineEventType::ASSET_ASSIGN,
            AssetMovement::TYPE_UNASSIGN => TimelineEventType::ASSET_UNASSIGN,
            AssetMovement::TYPE_LOAN => TimelineEventType::ASSET_LOAN,
            AssetMovement::TYPE_RETURN => TimelineEventType::ASSET_RETURN,
        ];
    }
}
