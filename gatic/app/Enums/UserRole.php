<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'Admin';
    case Editor = 'Editor';
    case Lector = 'Lector';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
}
