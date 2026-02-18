<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UsersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $role = 'all';

    #[Url(as: 'status')]
    public string $status = 'all';

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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'role', 'status']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->role !== 'all'
            || $this->status !== 'all';
    }

    public function render(): View
    {
        Gate::authorize('users.manage');

        $search = trim($this->search);
        $likePattern = $search !== '' ? '%'.$this->escapeLike($search).'%' : null;

        $users = User::query()
            ->when($likePattern, function ($query) use ($likePattern) {
                $query->where(function ($nestedQuery) use ($likePattern) {
                    $nestedQuery
                        ->where('name', 'like', $likePattern)
                        ->orWhere('email', 'like', $likePattern);
                });
            })
            ->when($this->role !== 'all', function ($query) {
                $query->where('role', $this->role);
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('is_active', $this->status === 'active');
            })
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.admin.users.users-index', [
            'users' => $users,
            'roles' => UserRole::values(),
            'summary' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'admins' => User::query()->where('role', UserRole::Admin->value)->count(),
            ],
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
