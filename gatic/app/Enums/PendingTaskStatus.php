<?php

namespace App\Enums;

enum PendingTaskStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Processing = 'processing';
    case Completed = 'completed';
    case PartiallyCompleted = 'partially_completed';
    case Cancelled = 'cancelled';

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
            self::Draft => 'Borrador',
            self::Ready => 'Listo',
            self::Processing => 'Procesando',
            self::Completed => 'Finalizado',
            self::PartiallyCompleted => 'Parcialmente finalizado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Badge CSS class for consistent UI styling
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'ops-status-chip ops-status-chip--secondary',
            self::Ready => 'ops-status-chip ops-status-chip--info',
            self::Processing => 'ops-status-chip ops-status-chip--warning',
            self::Completed => 'ops-status-chip ops-status-chip--success',
            self::PartiallyCompleted => 'ops-status-chip ops-status-chip--primary',
            self::Cancelled => 'ops-status-chip ops-status-chip--danger',
        };
    }

    /**
     * Check if task allows editing lines
     */
    public function allowsLineEditing(): bool
    {
        return $this === self::Draft;
    }
}
