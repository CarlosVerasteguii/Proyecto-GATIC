<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Usuarios</span>
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.users.create') }}">Crear usuario</a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->role?->value ?? $user->role }}</td>
                                        <td>
                                            @if ($user->is_active)
                                                <span class="badge text-bg-success">Activo</span>
                                            @else
                                                <span class="badge text-bg-secondary">Deshabilitado</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', ['user' => $user->id]) }}">
                                                Editar
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                wire:click="toggleActive({{ $user->id }})"
                                                wire:confirm="¿Confirmas que deseas {{ $user->is_active ? 'deshabilitar' : 'habilitar' }} a este usuario?"
                                            >
                                                {{ $user->is_active ? 'Deshabilitar' : 'Habilitar' }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
