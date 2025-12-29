<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Authorization\RoleAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function (User $user, string $ability): ?bool {
            return RoleAccess::isAdmin($user) ? true : null;
        });

        Gate::define('admin-only', static fn (User $user): bool => RoleAccess::isAdmin($user));

        Gate::define('users.manage', static fn (User $user): bool => RoleAccess::isAdmin($user));

        Gate::define(
            'attachments.manage',
            static fn (User $user): bool => RoleAccess::isAdminOrEditor($user)
        );

        Gate::define(
            'attachments.view',
            static fn (User $user): bool => RoleAccess::isAdminOrEditor($user)
        );

        Gate::define(
            'catalogs.manage',
            static fn (User $user): bool => RoleAccess::isAdminOrEditor($user)
        );
    }
}
