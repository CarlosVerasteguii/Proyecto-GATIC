<?php

namespace App\Enums;

enum PendingTaskLineStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Applied = 'applied';
    case Error = 'error';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    /**
     * UI label in Spanish
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Applied => 'Aplicado',
            self::Error => 'Error',
        };
    }

    /**
     * Badge CSS class for consistent UI styling
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-secondary text-white',
            self::Processing => 'bg-warning text-dark',
            self::Applied => 'bg-success text-white',
            self::Error => 'bg-danger text-white',
        };
    }
}
