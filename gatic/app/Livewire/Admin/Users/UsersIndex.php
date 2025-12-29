<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UsersIndex extends Component
{
    use WithPagination;

    public function toggleActive(int $userId): void
    {
        Gate::authorize('users.manage');

        if (auth()->id() === $userId) {
            session()->flash('error', 'No puedes deshabilitar tu propio usuario.');

            return;
        }

        $user = User::query()->findOrFail($userId);
        $user->is_active = ! $user->is_active;
        $user->save();

        session()->flash(
            'status',
            $user->is_active ? 'Usuario habilitado.' : 'Usuario deshabilitado.'
        );
    }

    public function render(): View
    {
        Gate::authorize('users.manage');

        return view('livewire.admin.users.users-index', [
            'users' => User::query()
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
