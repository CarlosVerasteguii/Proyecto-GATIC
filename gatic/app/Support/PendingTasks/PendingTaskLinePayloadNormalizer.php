<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Enums\PendingTaskLineType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PendingTaskLinePayloadNormalizer
{
    /**
     * @param  array{
     *     serial?: string,
     *     asset_tag?: string,
     *     quantity?: string|null,
     *     employee_id?: int|null,
     *     note?: string
     * }  $input
     * @return array{
     *     line_type: string,
     *     product_id: int|null,
     *     serial: string|null,
     *     asset_tag: string|null,
     *     quantity: int|null,
     *     employee_id: int|null,
     *     note: string
     * }
     *
     * @throws ValidationException
     */
    public function normalize(string $lineType, ?int $productId, array $input): array
    {
        return [
            'line_type' => $lineType,
            'product_id' => $productId,
            'serial' => $lineType === PendingTaskLineType::Serialized->value
                ? ($input['serial'] ?? '')
                : null,
            'asset_tag' => $lineType === PendingTaskLineType::Serialized->value
                ? ($input['asset_tag'] ?? '')
                : null,
            'quantity' => $this->normalizeQuantity($lineType, $input['quantity'] ?? null),
            'employee_id' => $input['employee_id'] ?? null,
            'note' => $input['note'] ?? '',
        ];
    }

    /**
     * @throws ValidationException
     */
    private function normalizeQuantity(string $lineType, ?string $quantity): ?int
    {
        if ($lineType !== PendingTaskLineType::Quantity->value) {
            return null;
        }

        return (int) Validator::make(
            ['quantity' => $quantity],
            ['quantity' => ['required', 'integer', 'min:1']],
        )->validate()['quantity'];
    }
}
