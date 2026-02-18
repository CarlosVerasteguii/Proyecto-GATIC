<div class="container position-relative admin-users-page">
    @php
        $resultCount = $users->total();
        $thirdKpiLabel = $this->hasActiveFilters() ? 'Resultados' : 'Administradores';
        $thirdKpiValue = $this->hasActiveFilters() ? $resultCount : $summary['admins'];
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Usuarios"
                filterId="admin-users-filters"
                class="admin-users-toolbar"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Administración', 'url' => route('admin.users.index')],
                        ['label' => 'Usuarios', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.column-manager table="admin-users" buttonClass="btn btn-sm btn-outline-secondary admin-users-columns-btn dropdown-toggle" />
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.users.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear usuario
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <label for="users-search" class="form-label">Buscar usuario</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input
                            id="users-search"
                            type="search"
                            class="form-control"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Nombre o correo electrónico…"
                            aria-label="Buscar usuario por nombre o correo electrónico"
                            autocomplete="off"
                        />
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="filter-role" class="form-label">Rol</label>
                        <select
                            id="filter-role"
                            class="form-select"
                            wire:model.live="role"
                            aria-label="Filtrar por rol"
                        >
                            <option value="all">Todos</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="filter-status" class="form-label">Estado</label>
                        <select
                            id="filter-status"
                            class="form-select"
                            wire:model.live="status"
                            aria-label="Filtrar por estado"
                        >
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Deshabilitados</option>
                        </select>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($this->hasActiveFilters())
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar filtros de usuarios"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

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

                <div class="row g-3 mb-3 admin-users-kpis">
                    <div class="col-12 col-md-4">
                        <article class="admin-users-kpi">
                            <div class="admin-users-kpi__label">Usuarios registrados</div>
                            <div class="admin-users-kpi__value">{{ number_format($summary['total']) }}</div>
                        </article>
                    </div>
                    <div class="col-12 col-md-4">
                        <article class="admin-users-kpi">
                            <div class="admin-users-kpi__label">Usuarios activos</div>
                            <div class="admin-users-kpi__value">{{ number_format($summary['active']) }}</div>
                        </article>
                    </div>
                    <div class="col-12 col-md-4">
                        <article class="admin-users-kpi">
                            <div class="admin-users-kpi__label">{{ $thirdKpiLabel }}</div>
                            <div class="admin-users-kpi__value">{{ number_format($thirdKpiValue) }}</div>
                        </article>
                    </div>
                </div>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultCount) }} resultado{{ $resultCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm align-middle mb-0 admin-users-table" data-column-table="admin-users">
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
                                @php
                                    $roleValue = $user->role?->value ?? $user->role;
                                    $roleClass = strtolower((string) $roleValue);
                                    $initials = collect(preg_split('/\s+/u', trim((string) $user->name)) ?: [])
                                        ->filter()
                                        ->take(2)
                                        ->map(fn (string $chunk): string => mb_strtoupper(mb_substr($chunk, 0, 1)))
                                        ->implode('');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <div class="admin-users-avatar" aria-hidden="true">{{ $initials !== '' ? $initials : 'U' }}</div>
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                                                <div class="small text-body-secondary text-truncate">ID {{ $user->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-decoration-none text-break" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill admin-users-role admin-users-role--{{ $roleClass }}">
                                            {{ $roleValue }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($user->is_active)
                                            <span class="badge rounded-pill admin-users-status admin-users-status--active">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge rounded-pill admin-users-status admin-users-status--inactive">
                                                <i class="bi bi-slash-circle-fill" aria-hidden="true"></i>
                                                Deshabilitado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2 admin-users-actions">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', ['user' => $user->id]) }}">
                                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Editar
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                wire:click="toggleActive({{ $user->id }})"
                                                wire:confirm="¿Confirmas que deseas {{ $user->is_active ? 'deshabilitar' : 'habilitar' }} a este usuario?"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleActive"
                                                aria-label="{{ $user->is_active ? 'Deshabilitar' : 'Habilitar' }} usuario {{ $user->name }}"
                                            >
                                                <i class="bi {{ $user->is_active ? 'bi-person-dash' : 'bi-person-check' }} me-1" aria-hidden="true"></i>
                                                {{ $user->is_active ? 'Deshabilitar' : 'Habilitar' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        @if ($this->hasActiveFilters())
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
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
                                        @endif
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
