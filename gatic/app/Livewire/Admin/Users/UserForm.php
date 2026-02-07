<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Settings\UserSettingsStore;
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

    public string $department = '';

    public string $position = '';

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
        $this->role = $model->role->value;
        $this->is_active = (bool) $model->is_active;
        $this->department = $model->department ?? '';
        $this->position = $model->position ?? '';
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
                'department' => ['nullable', 'string', 'max:255'],
                'position' => ['nullable', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ];
        }

        return [
            'role' => ['required', $roleRule],
            'is_active' => ['boolean'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'password' => $this->password !== ''
                ? ['nullable', 'confirmed', Password::defaults()]
                : ['nullable', 'confirmed'],
        ];
    }

    /**
     * UI copy must be Spanish (see project-context).
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'department.max' => 'El campo Departamento no debe exceder 255 caracteres.',
            'position.max' => 'El campo Puesto no debe exceder 255 caracteres.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('users.manage');

        $this->validate($this->rules(), $this->messages());

        if (! $this->userId) {
            $user = User::query()->create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => $this->password,
                'is_active' => true,
                'department' => $this->normalizeOptionalText($this->department),
                'position' => $this->normalizeOptionalText($this->position),
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

        $user->role = $newRole;
        $user->is_active = $this->is_active;
        $user->department = $this->normalizeOptionalText($this->department);
        $user->position = $this->normalizeOptionalText($this->position);

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

    public function resetUiPreferences(): void
    {
        Gate::authorize('users.manage');

        if (! $this->userId) {
            return;
        }

        $user = User::query()->findOrFail($this->userId);
        app(UserSettingsStore::class)->forgetUiPreferencesForUser($user->id);

        session()->flash('status', 'Preferencias UI restablecidas.');
    }

    private function normalizeOptionalText(string $value): ?string
    {
        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
