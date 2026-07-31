<?php

declare(strict_types=1);

namespace Tests\Unit\Timeline;

use App\Models\ProductQuantityMovement;
use App\Support\Timeline\ProductQuantityMovementEventTypeMap;
use App\Support\Timeline\TimelineEventType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductQuantityMovementEventTypeMapTest extends TestCase
{
    #[Test]
    public function it_maps_product_quantity_movement_directions_to_timeline_event_types_and_labels(): void
    {
        $this->assertSame(
            TimelineEventType::PRODUCT_QTY_IN,
            ProductQuantityMovementEventTypeMap::eventTypeForDirection(ProductQuantityMovement::DIRECTION_IN),
        );
        $this->assertSame(
            'Entrada',
            ProductQuantityMovementEventTypeMap::labelForDirection(ProductQuantityMovement::DIRECTION_IN),
        );

        $this->assertSame(
            TimelineEventType::PRODUCT_QTY_OUT,
            ProductQuantityMovementEventTypeMap::eventTypeForDirection(ProductQuantityMovement::DIRECTION_OUT),
        );
        $this->assertSame(
            'Salida',
            ProductQuantityMovementEventTypeMap::labelForDirection(ProductQuantityMovement::DIRECTION_OUT),
        );
    }
}
