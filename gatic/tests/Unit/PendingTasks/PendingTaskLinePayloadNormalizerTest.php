<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Enums\PendingTaskLineType;
use App\Support\PendingTasks\PendingTaskLinePayloadNormalizer;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskLinePayloadNormalizerTest extends TestCase
{
    #[Test]
    public function it_builds_serialized_payload_without_quantity(): void
    {
        $payload = (new PendingTaskLinePayloadNormalizer)->normalize(
            PendingTaskLineType::Serialized->value,
            15,
            [
                'serial' => 'SER-001',
                'asset_tag' => 'TAG-001',
                'quantity' => '9',
                'employee_id' => 22,
                'note' => 'Nota serializada',
            ],
        );

        $this->assertSame([
            'line_type' => PendingTaskLineType::Serialized->value,
            'product_id' => 15,
            'serial' => 'SER-001',
            'asset_tag' => 'TAG-001',
            'quantity' => null,
            'employee_id' => 22,
            'note' => 'Nota serializada',
        ], $payload);
    }

    #[Test]
    public function it_casts_quantity_payload_to_integer_and_clears_serial_fields(): void
    {
        $payload = (new PendingTaskLinePayloadNormalizer)->normalize(
            PendingTaskLineType::Quantity->value,
            30,
            [
                'serial' => 'IGNORED',
                'asset_tag' => 'IGNORED',
                'quantity' => '7',
                'employee_id' => 5,
                'note' => 'Nota cantidad',
            ],
        );

        $this->assertSame([
            'line_type' => PendingTaskLineType::Quantity->value,
            'product_id' => 30,
            'serial' => null,
            'asset_tag' => null,
            'quantity' => 7,
            'employee_id' => 5,
            'note' => 'Nota cantidad',
        ], $payload);
    }

    #[Test]
    public function it_throws_a_validation_exception_when_quantity_is_not_a_positive_integer(): void
    {
        $this->expectException(ValidationException::class);

        (new PendingTaskLinePayloadNormalizer)->normalize(
            PendingTaskLineType::Quantity->value,
            30,
            [
                'quantity' => '0',
                'employee_id' => 5,
                'note' => 'Nota cantidad',
            ],
        );
    }
}
