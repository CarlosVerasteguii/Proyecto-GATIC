<?php

namespace App\Enums;

enum PendingTaskLineType: string
{
    case Serialized = 'serialized';
    case Quantity = 'quantity';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }

    /**
     * UI label in Spanish
     */
    public function label(): string
    {
        return match ($this) {
            self::Serialized => 'Serializado',
            self::Quantity => 'Por cantidad',
        };
    }
}
