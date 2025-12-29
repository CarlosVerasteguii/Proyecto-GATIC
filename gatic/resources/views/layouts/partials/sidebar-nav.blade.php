@php
    $dashboardActive = request()->routeIs('dashboard');
    $adminUsersActive = request()->routeIs('admin.users.*');
@endphp

<ul class="nav nav-pills flex-column gap-1">
    <li class="nav-item">
        <a
            class="nav-link @if ($dashboardActive) active @endif"
            href="{{ route('dashboard') }}"
            @if ($dashboardActive) aria-current="page" @endif
        >
            Inicio
        </a>
    </li>

    @can('users.manage')
        <li class="nav-item">
            <a
                class="nav-link @if ($adminUsersActive) active @endif"
                href="{{ route('admin.users.index') }}"
                @if ($adminUsersActive) aria-current="page" @endif
            >
                Usuarios
            </a>
        </li>
    @endcan
</ul>
