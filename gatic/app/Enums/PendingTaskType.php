<?php

namespace App\Enums;

enum PendingTaskType: string
{
    case StockOut = 'stock_out';
    case StockIn = 'stock_in';
    case Assign = 'assign';
    case Loan = 'loan';
    case Return = 'return';
    case Retirement = 'retirement';

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
            self::StockOut => 'Salida',
            self::StockIn => 'Entrada',
            self::Assign => 'Asignación',
            self::Loan => 'Préstamo',
            self::Return => 'Devolución',
            self::Retirement => 'Retiro',
        };
    }
}
