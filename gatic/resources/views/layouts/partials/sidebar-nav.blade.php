@php
    $dashboardActive = request()->routeIs('dashboard');
    $adminUsersActive = request()->routeIs('admin.users.*');
    $catalogsCategoriesActive = request()->routeIs('catalogs.categories.*');
    $catalogsBrandsActive = request()->routeIs('catalogs.brands.*');
    $catalogsLocationsActive = request()->routeIs('catalogs.locations.*');
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

    @can('catalogs.manage')
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsCategoriesActive) active @endif"
                href="{{ route('catalogs.categories.index') }}"
                @if ($catalogsCategoriesActive) aria-current="page" @endif
            >
                Categorías
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsBrandsActive) active @endif"
                href="{{ route('catalogs.brands.index') }}"
                @if ($catalogsBrandsActive) aria-current="page" @endif
            >
                Marcas
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsLocationsActive) active @endif"
                href="{{ route('catalogs.locations.index') }}"
                @if ($catalogsLocationsActive) aria-current="page" @endif
            >
                Ubicaciones
            </a>
        </li>
    @endcan
</ul>
