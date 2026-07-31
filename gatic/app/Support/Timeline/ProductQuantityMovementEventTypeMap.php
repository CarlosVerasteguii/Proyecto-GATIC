<?php

namespace App\Support\Timeline;

use App\Models\ProductQuantityMovement;

/**
 * Shared mapping between ProductQuantityMovement direction and timeline event types.
 *
 * This exists to avoid drift between per-entity timeline and dashboard recent activity feeds.
 */
final class ProductQuantityMovementEventTypeMap
{
    public static function eventTypeForDirection(string $direction): string
    {
        return $direction === ProductQuantityMovement::DIRECTION_IN
            ? TimelineEventType::PRODUCT_QTY_IN
            : TimelineEventType::PRODUCT_QTY_OUT;
    }

    public static function labelForDirection(string $direction): string
    {
        return $direction === ProductQuantityMovement::DIRECTION_IN ? 'Entrada' : 'Salida';
    }
}
