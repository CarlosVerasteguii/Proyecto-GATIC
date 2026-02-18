<div class="container position-relative admin-user-form-page">
    @php
        $pageTitle = $isEdit ? 'Editar usuario' : 'Crear usuario';
        $pageSubtitle = $isEdit
            ? 'Actualiza permisos, estado y datos organizacionales del usuario.'
            : 'Configura el acceso inicial al sistema con rol y contraseña segura.';

        $roleUi = [
            'Admin' => [
                'badge' => 'admin',
                'icon' => 'bi-shield-lock',
                'label' => 'Acceso total a módulos de operación y administración.',
            ],
            'Editor' => [
                'badge' => 'editor',
                'icon' => 'bi-pencil-square',
                'label' => 'Permisos de gestión operativa sin control total administrativo.',
            ],
            'Lector' => [
                'badge' => 'lector',
                'icon' => 'bi-eye',
                'label' => 'Acceso de consulta, ideal para perfiles de solo lectura.',
            ],
        ];

        $currentRoleUi = $roleUi[$role] ?? $roleUi['Lector'];
        $currentStatusLabel = $isEdit
            ? ($is_active ? 'Activo' : 'Deshabilitado')
            : 'Activo al crear';
        $currentStatusClass = $isEdit
            ? ($is_active ? 'active' : 'inactive')
            : 'active';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <div class="card admin-user-form-card">
                <div class="card-header admin-user-form-card__header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="min-w-0">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Administración', 'url' => route('admin.users.index')],
                            ['label' => $pageTitle, 'url' => null],
                        ]" />
                        <h1 class="h5 mb-1">{{ $pageTitle }}</h1>
                        <p class="text-body-secondary mb-0 admin-user-form-card__subtitle">{{ $pageSubtitle }}</p>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver
                    </a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success d-flex align-items-center gap-2" role="status" aria-live="polite">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            Revisa los campos marcados para continuar.
                        </div>
                    @endif

                    <form wire:submit="save">
                        <div class="row g-3">
                            <div class="col-12 col-xl-8">
                                <section class="admin-user-form-section">
                                    <h2 class="admin-user-form-section__title">
                                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                                        Perfil
                                    </h2>

                                    <div class="row g-3">
                                        @if (! $isEdit)
                                            <div class="col-12">
                                                <label for="name" class="form-label">Nombre completo</label>
                                                <input
                                                    id="name"
                                                    name="name"
                                                    type="text"
                                                    class="form-control @error('name') is-invalid @enderror"
                                                    wire:model.blur="name"
                                                    placeholder="Ejemplo: Ana Martínez…"
                                                    autocomplete="name"
                                                    maxlength="255"
                                                    required
                                                >
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="email" class="form-label">Correo electrónico</label>
                                                <input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    wire:model.blur="email"
                                                    placeholder="Ejemplo: usuario@gatic.local…"
                                                    autocomplete="email"
                                                    spellcheck="false"
                                                    maxlength="255"
                                                    required
                                                >
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="col-12">
                                                <label for="name" class="form-label">Nombre completo</label>
                                                <input
                                                    id="name"
                                                    name="name"
                                                    type="text"
                                                    class="form-control admin-user-form-readonly"
                                                    value="{{ $this->name }}"
                                                    readonly
                                                    aria-readonly="true"
                                                >
                                            </div>

                                            <div class="col-12">
                                                <label for="email" class="form-label">Correo electrónico</label>
                                                <input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    class="form-control admin-user-form-readonly"
                                                    value="{{ $this->email }}"
                                                    readonly
                                                    aria-readonly="true"
                                                >
                                            </div>
                                        @endif

                                        <div class="col-12 col-md-6">
                                            <label for="department" class="form-label">Departamento</label>
                                            <input
                                                id="department"
                                                name="department"
                                                type="text"
                                                class="form-control @error('department') is-invalid @enderror"
                                                wire:model.blur="department"
                                                placeholder="Ejemplo: Operaciones…"
                                                autocomplete="organization"
                                                maxlength="255"
                                            >
                                            @error('department')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="position" class="form-label">Puesto</label>
                                            <input
                                                id="position"
                                                name="position"
                                                type="text"
                                                class="form-control @error('position') is-invalid @enderror"
                                                wire:model.blur="position"
                                                placeholder="Ejemplo: Supervisor de almacén…"
                                                autocomplete="organization-title"
                                                maxlength="255"
                                            >
                                            @error('position')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                <section class="admin-user-form-section">
                                    <h2 class="admin-user-form-section__title">
                                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                                        Permisos
                                    </h2>

                                    <div class="row g-3 align-items-start">
                                        <div class="col-12 col-lg-7">
                                            <label for="role" class="form-label">Rol del usuario</label>
                                            <select
                                                id="role"
                                                name="role"
                                                class="form-select @error('role') is-invalid @enderror"
                                                wire:model.live="role"
                                                aria-label="Seleccionar rol del usuario"
                                                required
                                            >
                                                @foreach ($roles as $roleOption)
                                                    <option value="{{ $roleOption }}" wire:key="role-option-{{ $roleOption }}">{{ $roleOption }}</option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-lg-5">
                                            <article class="admin-user-role-help admin-user-role-help--{{ $currentRoleUi['badge'] }}">
                                                <div class="admin-user-role-help__title">
                                                    <i class="bi {{ $currentRoleUi['icon'] }}" aria-hidden="true"></i>
                                                    {{ $role }}
                                                </div>
                                                <p class="mb-0 small">{{ $currentRoleUi['label'] }}</p>
                                            </article>
                                        </div>
                                    </div>

                                    @if ($isEdit)
                                        <div class="form-check form-switch mt-3">
                                            <input
                                                id="is_active"
                                                name="is_active"
                                                type="checkbox"
                                                class="form-check-input @error('is_active') is-invalid @enderror"
                                                wire:model.live="is_active"
                                            >
                                            <label class="form-check-label" for="is_active">Usuario activo</label>
                                            @error('is_active')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif
                                </section>

                                <section
                                    class="admin-user-form-section"
                                    x-data="{
                                        showPassword: false,
                                        showPasswordConfirmation: false,
                                        copiedField: null,
                                        async copyFromRef(refName) {
                                            const input = this.$refs[refName];
                                            if (!input || !input.value) {
                                                return;
                                            }

                                            try {
                                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                                    await navigator.clipboard.writeText(input.value);
                                                } else {
                                                    input.focus();
                                                    input.select();
                                                    document.execCommand('copy');
                                                    input.setSelectionRange(input.value.length, input.value.length);
                                                }

                                                this.copiedField = refName;
                                                setTimeout(() => {
                                                    if (this.copiedField === refName) {
                                                        this.copiedField = null;
                                                    }
                                                }, 1500);
                                            } catch (_) {
                                                // no-op
                                            }
                                        }
                                    }"
                                >
                                    <h2 class="admin-user-form-section__title">
                                        <i class="bi bi-key" aria-hidden="true"></i>
                                        Seguridad
                                    </h2>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="password" class="form-label">{{ $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                                            <div class="input-group admin-user-password-group">
                                                <input
                                                    id="password"
                                                    name="password"
                                                    x-ref="passwordInput"
                                                    :type="showPassword ? 'text' : 'password'"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    wire:model.blur="password"
                                                    placeholder="{{ $isEdit ? 'Escribe una nueva contraseña para actualizarla…' : 'Escribe una contraseña segura…' }}"
                                                    autocomplete="new-password"
                                                    spellcheck="false"
                                                    @if (! $isEdit) required @endif
                                                >
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary admin-user-password-btn"
                                                    x-on:click="showPassword = !showPassword"
                                                    x-bind:aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                                    x-bind:title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                                >
                                                    <i class="bi" x-bind:class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary admin-user-password-btn"
                                                    x-on:click="copyFromRef('passwordInput')"
                                                    aria-label="Copiar contraseña"
                                                    title="Copiar contraseña"
                                                >
                                                    <i class="bi" x-bind:class="copiedField === 'passwordInput' ? 'bi-check2' : 'bi-clipboard'" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Se permite copiar y pegar en este campo.</div>
                                        </div>

                                        <div class="col-12">
                                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                            <div class="input-group admin-user-password-group">
                                                <input
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    x-ref="passwordConfirmationInput"
                                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                                    class="form-control"
                                                    wire:model.blur="password_confirmation"
                                                    placeholder="Repite la contraseña para confirmar…"
                                                    autocomplete="new-password"
                                                    spellcheck="false"
                                                    @if (! $isEdit) required @endif
                                                >
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary admin-user-password-btn"
                                                    x-on:click="showPasswordConfirmation = !showPasswordConfirmation"
                                                    x-bind:aria-label="showPasswordConfirmation ? 'Ocultar confirmación de contraseña' : 'Mostrar confirmación de contraseña'"
                                                    x-bind:title="showPasswordConfirmation ? 'Ocultar confirmación de contraseña' : 'Mostrar confirmación de contraseña'"
                                                >
                                                    <i class="bi" x-bind:class="showPasswordConfirmation ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary admin-user-password-btn"
                                                    x-on:click="copyFromRef('passwordConfirmationInput')"
                                                    aria-label="Copiar confirmación de contraseña"
                                                    title="Copiar confirmación de contraseña"
                                                >
                                                    <i class="bi" x-bind:class="copiedField === 'passwordConfirmationInput' ? 'bi-check2' : 'bi-clipboard'" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <div class="d-flex flex-wrap gap-2 admin-user-form-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                    >
                                        <span wire:loading.remove wire:target="save">
                                            {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
                                        </span>
                                        <span
                                            wire:loading.inline
                                            wire:target="save"
                                        >
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                Guardando…
                                            </span>
                                        </span>
                                    </button>

                                    @if ($isEdit)
                                        <button
                                            type="button"
                                            class="btn btn-outline-warning"
                                            wire:click="resetUiPreferences"
                                            wire:confirm="¿Restablecer las preferencias UI de este usuario?"
                                            wire:loading.attr="disabled"
                                            wire:target="resetUiPreferences"
                                        >
                                            Restablecer preferencias UI
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <aside class="admin-user-form-summary">
                                    <h2 class="admin-user-form-summary__title">Resumen de acceso</h2>
                                    <dl class="admin-user-form-summary__meta mb-0">
                                        <div>
                                            <dt>Modo</dt>
                                            <dd>{{ $isEdit ? 'Edición' : 'Alta de usuario' }}</dd>
                                        </div>
                                        <div>
                                            <dt>Estado</dt>
                                            <dd>
                                                <span class="admin-user-status admin-user-status--{{ $currentStatusClass }}">
                                                    {{ $currentStatusLabel }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Rol seleccionado</dt>
                                            <dd>{{ $role }}</dd>
                                        </div>
                                    </dl>

                                    <p class="admin-user-form-summary__hint mb-0">
                                        Usa esta configuración para mantener un acceso mínimo necesario por rol y reducir riesgo operativo.
                                    </p>
                                </aside>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
