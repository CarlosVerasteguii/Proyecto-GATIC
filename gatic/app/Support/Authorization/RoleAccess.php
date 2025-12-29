<?php

namespace App\Support\Authorization;

use App\Enums\UserRole;
use App\Models\User;

final class RoleAccess
{
    public static function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public static function isAdminOrEditor(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Editor], true);
    }
}
