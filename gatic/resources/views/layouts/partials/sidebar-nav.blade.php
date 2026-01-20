@php
    $dashboardActive = request()->routeIs('dashboard');
    $adminUsersActive = request()->routeIs('admin.users.*');
    $employeesActive = request()->routeIs('employees.*');
    $pendingTasksActive = request()->routeIs('pending-tasks.*');
    $catalogsCategoriesActive = request()->routeIs('catalogs.categories.*');
    $catalogsBrandsActive = request()->routeIs('catalogs.brands.*');
    $catalogsLocationsActive = request()->routeIs('catalogs.locations.*');
    $catalogsTrashActive = request()->routeIs('catalogs.trash.*');
    $inventorySearchActive = request()->routeIs('inventory.search');
    $inventoryProductsActive = request()->routeIs('inventory.products.*');
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

    @can('inventory.view')
        <li class="nav-item">
            <a
                class="nav-link @if ($inventorySearchActive) active @endif"
                href="{{ route('inventory.search') }}"
                @if ($inventorySearchActive) aria-current="page" @endif
            >
                Inventario &gt; B&uacute;squeda
            </a>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($inventoryProductsActive) active @endif"
                href="{{ route('inventory.products.index') }}"
                @if ($inventoryProductsActive) aria-current="page" @endif
            >
                Inventario &gt; Productos
            </a>
        </li>
    @endcan

    @can('inventory.manage')
        <li class="nav-item">
            <a
                class="nav-link @if ($pendingTasksActive) active @endif"
                href="{{ route('pending-tasks.index') }}"
                @if ($pendingTasksActive) aria-current="page" @endif
            >
                <i class="bi bi-list-task me-2"></i>
                Tareas Pendientes
            </a>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($employeesActive) active @endif"
                href="{{ route('employees.index') }}"
                @if ($employeesActive) aria-current="page" @endif
            >
                Empleados
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
        @can('admin-only')
            <li class="nav-item">
                <a
                    class="nav-link @if ($catalogsTrashActive) active @endif"
                    href="{{ route('catalogs.trash.index') }}"
                    @if ($catalogsTrashActive) aria-current="page" @endif
                >
                    Papelera
                </a>
            </li>
        @endcan
    @endcan
</ul>
