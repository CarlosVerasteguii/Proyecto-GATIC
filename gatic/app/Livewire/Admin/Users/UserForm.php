<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = UserRole::Lector->value;

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?string $user = null): void
    {
        Gate::authorize('users.manage');

        if (! $user) {
            return;
        }

        if (! ctype_digit($user)) {
            abort(404);
        }

        $this->userId = (int) $user;

        $model = User::query()->findOrFail($this->userId);
        $this->name = $model->name;
        $this->email = $model->email;
        $this->role = ($model->role instanceof UserRole) ? $model->role->value : (string) $model->role;
        $this->is_active = (bool) $model->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $roleRule = Rule::in(UserRole::values());

        if (! $this->userId) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'role' => ['required', $roleRule],
                'password' => ['required', 'confirmed', Password::defaults()],
            ];
        }

        return [
            'role' => ['required', $roleRule],
            'is_active' => ['boolean'],
            'password' => array_values(array_filter([
                'nullable',
                'confirmed',
                $this->password !== '' ? Password::defaults() : null,
            ])),
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('users.manage');

        $this->validate();

        if (! $this->userId) {
            $user = User::query()->create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => $this->password,
                'is_active' => true,
            ]);

            return redirect()
                ->route('admin.users.edit', ['user' => $user->id])
                ->with('status', 'Usuario creado.');
        }

        if (auth()->id() === $this->userId && ! $this->is_active) {
            $this->addError('is_active', 'No puedes deshabilitar tu propio usuario.');

            return null;
        }

        $user = User::query()->findOrFail($this->userId);

        $newRole = UserRole::tryFrom($this->role);
        if (! $newRole) {
            $this->addError('role', 'Rol inválido.');

            return null;
        }

        if (auth()->id() === $user->id && $newRole !== $user->role) {
            $this->addError('role', 'No puedes cambiar tu propio rol. Pide a otro Admin que lo haga.');

            return null;
        }

        $isDemotingOrDisablingAdmin =
            $user->role === UserRole::Admin
            && (
                $newRole !== UserRole::Admin
                || ! $this->is_active
            );

        if ($isDemotingOrDisablingAdmin) {
            $otherActiveAdminCount = User::query()
                ->where('id', '!=', $user->id)
                ->where('role', UserRole::Admin->value)
                ->where('is_active', true)
                ->count();

            if ($otherActiveAdminCount < 1) {
                $this->addError('role', 'Debe existir al menos un Admin activo.');

                return null;
            }
        }

        $user->role = $this->role;
        $user->is_active = $this->is_active;

        if ($this->password !== '') {
            $user->password = $this->password;
        }

        $user->save();

        return redirect()
            ->route('admin.users.edit', ['user' => $user->id])
            ->with('status', 'Usuario actualizado.');
    }

    public function render(): View
    {
        Gate::authorize('users.manage');

        return view('livewire.admin.users.user-form', [
            'roles' => UserRole::values(),
            'isEdit' => (bool) $this->userId,
        ]);
    }
}
