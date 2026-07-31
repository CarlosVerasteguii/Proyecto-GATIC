<?php

declare(strict_types=1);

namespace Tests\Unit\Timeline;

use App\Models\AssetMovement;
use App\Support\Timeline\AssetMovementEventTypeMap;
use App\Support\Timeline\TimelineEventType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetMovementEventTypeMapTest extends TestCase
{
    #[Test]
    public function it_maps_asset_movement_types_to_timeline_event_types(): void
    {
        $this->assertSame([
            AssetMovement::TYPE_ASSIGN => TimelineEventType::ASSET_ASSIGN,
            AssetMovement::TYPE_UNASSIGN => TimelineEventType::ASSET_UNASSIGN,
            AssetMovement::TYPE_LOAN => TimelineEventType::ASSET_LOAN,
            AssetMovement::TYPE_RETURN => TimelineEventType::ASSET_RETURN,
        ], AssetMovementEventTypeMap::map());
    }
}
