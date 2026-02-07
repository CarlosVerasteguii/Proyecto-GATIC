<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ $isEdit ? 'Editar usuario' : 'Crear usuario' }}</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.index') }}">Volver</a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form wire:submit="save">
                        @if (! $isEdit)
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre</label>
                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electr&oacute;nico</label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="department" class="form-label">Departamento</label>
                                <input id="department" name="department" type="text" class="form-control @error('department') is-invalid @enderror" wire:model="department">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="position" class="form-label">Puesto</label>
                                <input id="position" name="position" type="text" class="form-control @error('position') is-invalid @enderror" wire:model="position">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre</label>
                                <input id="name" name="name" type="text" class="form-control" value="{{ $this->name }}" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electr&oacute;nico</label>
                                <input id="email" name="email" type="email" class="form-control" value="{{ $this->email }}" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="department" class="form-label">Departamento</label>
                                <input id="department" name="department" type="text" class="form-control @error('department') is-invalid @enderror" wire:model="department">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="position" class="form-label">Puesto</label>
                                <input id="position" name="position" type="text" class="form-control @error('position') is-invalid @enderror" wire:model="position">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" wire:model="role">
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($isEdit)
                            <div class="mb-3 form-check">
                                <input id="is_active" name="is_active" type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" wire:model="is_active">
                                <label class="form-check-label" for="is_active">Usuario activo</label>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <hr>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ $isEdit ? 'Nueva contrase&ntilde;a (opcional)' : 'Contrase&ntilde;a' }}</label>
                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar contrase&ntilde;a</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" wire:model="password_confirmation">
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
                            </button>

                            @if ($isEdit)
                                <button
                                    type="button"
                                    class="btn btn-outline-warning"
                                    wire:click="resetUiPreferences"
                                    wire:confirm="¿Restablecer las preferencias UI de este usuario?"
                                >
                                    Restablecer preferencias UI
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
