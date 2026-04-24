<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\InventoryValuePayloadBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryValuePayloadBuilderTest extends TestCase
{
    #[Test]
    public function it_formats_inventory_value_payload_and_adds_otros_when_more_than_top_n(): void
    {
        $payload = app(InventoryValuePayloadBuilder::class)->build(
            totalValue: 25000.0,
            categoryRows: [
                ['id' => 10, 'name' => 'Laptops', 'total' => 20000.0],
            ],
            categoriesCount: 2,
            brandRows: [
                ['id' => null, 'name' => 'Sin marca', 'total' => 15000.0],
            ],
            brandsCount: 2,
            topN: 1,
        );

        $this->assertSame([
            'totalInventoryValue' => '25000.00',
            'valueByCategory' => [
                ['name' => 'Laptops', 'value' => '20000.00', 'id' => 10],
                ['name' => 'Otros', 'value' => '5000.00', 'id' => null],
            ],
            'valueByBrand' => [
                ['name' => 'Sin marca', 'value' => '15000.00', 'id' => null],
                ['name' => 'Otros', 'value' => '10000.00', 'id' => null],
            ],
        ], $payload);
    }

    #[Test]
    public function it_uses_the_rounded_total_when_deciding_whether_to_append_otros(): void
    {
        $payload = app(InventoryValuePayloadBuilder::class)->build(
            totalValue: 100.0049,
            categoryRows: [
                ['id' => 10, 'name' => 'Laptops', 'total' => 100.0],
            ],
            categoriesCount: 2,
            brandRows: [
                ['id' => 20, 'name' => 'Dell', 'total' => 100.0],
            ],
            brandsCount: 2,
            topN: 1,
        );

        $this->assertSame('100.00', $payload['totalInventoryValue']);
        $this->assertSame([
            ['name' => 'Laptops', 'value' => '100.00', 'id' => 10],
        ], $payload['valueByCategory']);
        $this->assertSame([
            ['name' => 'Dell', 'value' => '100.00', 'id' => 20],
        ], $payload['valueByBrand']);
    }
}
