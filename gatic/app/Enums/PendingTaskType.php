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

    /**
     * Check if this task type supports serialized lines (Assets).
     *
     * Matriz de aplicación task.type × line_type:
     * - assign/loan/return: Soportan serializado (Asset movements)
     * - stock_in/stock_out/retirement: Solo cantidad (no manejan Assets)
     */
    public function supportsSerialized(): bool
    {
        return match ($this) {
            self::Assign, self::Loan, self::Return => true,
            self::StockIn, self::StockOut, self::Retirement => false,
        };
    }

    /**
     * Check if this task type supports quantity lines (Product qty movements).
     *
     * Todos los tipos soportan renglones por cantidad.
     */
    public function supportsQuantity(): bool
    {
        return true;
    }

    /**
     * Get the direction for quantity movements ('in' or 'out').
     *
     * - stock_in/return: Entradas (incrementan stock)
     * - stock_out/assign/loan/retirement: Salidas (decrementan stock)
     */
    public function quantityDirection(): string
    {
        return match ($this) {
            self::StockIn, self::Return => 'in',
            self::StockOut, self::Assign, self::Loan, self::Retirement => 'out',
        };
    }

    /**
     * Get human-readable error message when line type is not supported.
     */
    public function unsupportedLineTypeMessage(PendingTaskLineType $lineType): string
    {
        return match ($lineType) {
            PendingTaskLineType::Serialized => "El tipo de tarea \"{$this->label()}\" no admite renglones serializados (Assets).",
            PendingTaskLineType::Quantity => "El tipo de tarea \"{$this->label()}\" no admite renglones por cantidad.",
        };
    }
}
