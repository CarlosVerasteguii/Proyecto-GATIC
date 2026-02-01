<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <x-ui.toolbar title="Usuarios" :filtersCollapsible="false">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Administración', 'url' => route('admin.users.index')],
                        ['label' => 'Usuarios', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.column-manager table="admin-users" />
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.users.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear usuario
                    </a>
                </x-slot:actions>

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

                <div class="table-responsive-xl">
                    <table class="table table-sm table-striped align-middle mb-0" data-column-table="admin-users">
                        <thead>
                            <tr>
                                <th data-column-key="name" data-column-required="true">Nombre</th>
                                <th data-column-key="email">Email</th>
                                <th data-column-key="role">Rol</th>
                                <th data-column-key="status">Estado</th>
                                <th data-column-key="actions" data-column-required="true" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
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
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-ui.empty-state
                                            icon="bi-people"
                                            title="No hay usuarios"
                                            description="Crea usuarios para dar acceso al sistema."
                                            compact
                                        >
                                            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear usuario
                                            </a>
                                        </x-ui.empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
