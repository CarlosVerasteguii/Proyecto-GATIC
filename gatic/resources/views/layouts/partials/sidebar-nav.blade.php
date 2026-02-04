@php
    $dashboardActive = request()->routeIs('dashboard');
    $adminUsersActive = request()->routeIs('admin.users.*');
    $adminTrashActive = request()->routeIs('admin.trash.*');
    $adminErrorReportsActive = request()->routeIs('admin.error-reports.*');
    $employeesActive = request()->routeIs('employees.*');
    $pendingTasksActive = request()->routeIs('pending-tasks.*');
    $catalogsCategoriesActive = request()->routeIs('catalogs.categories.*');
    $catalogsBrandsActive = request()->routeIs('catalogs.brands.*');
    $catalogsLocationsActive = request()->routeIs('catalogs.locations.*');
    $catalogsSuppliersActive = request()->routeIs('catalogs.suppliers.*');
    $catalogsTrashActive = request()->routeIs('catalogs.trash.*');
    $inventorySearchActive = request()->routeIs('inventory.search');
    $inventoryProductsActive = request()->routeIs('inventory.products.*');
    $showAdminSection = auth()->user()->can('users.manage')
        || auth()->user()->can('admin-only');
@endphp

<ul class="nav nav-pills flex-column gap-0">
    <li class="sidebar-section px-2">
        <div class="text-uppercase small text-secondary-emphasis">Navegación principal</div>
    </li>
    <li class="nav-item">
        <a
            class="nav-link @if ($dashboardActive) active @endif"
            href="{{ route('dashboard') }}"
            data-tooltip="Inicio"
            @if ($dashboardActive) aria-current="page" @endif
        >
            <i class="bi bi-house nav-icon" aria-hidden="true"></i>
            <span class="nav-text">Inicio</span>
        </a>
    </li>

    @can('inventory.view')
        <li class="nav-item">
            <a
                class="nav-link @if ($inventorySearchActive) active @endif"
                href="{{ route('inventory.search') }}"
                data-tooltip="Búsqueda"
                @if ($inventorySearchActive) aria-current="page" @endif
            >
                <i class="bi bi-search nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Búsqueda</span>
            </a>
        </li>

        <li class="sidebar-divider px-2">
            <hr class="my-1 border-secondary opacity-25" />
        </li>

        <li class="sidebar-section px-2">
            <div class="text-uppercase small text-secondary-emphasis">Inventario</div>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($inventoryProductsActive) active @endif"
                href="{{ route('inventory.products.index') }}"
                data-tooltip="Productos"
                @if ($inventoryProductsActive) aria-current="page" @endif
            >
                <i class="bi bi-box-seam nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Productos</span>
            </a>
        </li>
    @endcan

    @can('inventory.manage')
        <li class="sidebar-divider px-2">
            <hr class="my-1 border-secondary opacity-25" />
        </li>

        <li class="sidebar-section px-2">
            <div class="text-uppercase small text-secondary-emphasis">Operaciones</div>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($pendingTasksActive) active @endif"
                href="{{ route('pending-tasks.index') }}"
                data-tooltip="Tareas Pendientes"
                @if ($pendingTasksActive) aria-current="page" @endif
            >
                <i class="bi bi-list-task nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Tareas Pendientes</span>
            </a>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($employeesActive) active @endif"
                href="{{ route('employees.index') }}"
                data-tooltip="Empleados"
                @if ($employeesActive) aria-current="page" @endif
            >
                <i class="bi bi-person-badge nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Empleados</span>
            </a>
        </li>
    @endcan

    @can('catalogs.manage')
        <li class="sidebar-divider px-2">
            <hr class="my-1 border-secondary opacity-25" />
        </li>

        <li class="sidebar-section px-2">
            <div class="text-uppercase small text-secondary-emphasis">Catálogos</div>
        </li>

        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsCategoriesActive) active @endif"
                href="{{ route('catalogs.categories.index') }}"
                data-tooltip="Categorías"
                @if ($catalogsCategoriesActive) aria-current="page" @endif
            >
                <i class="bi bi-folder nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Categorías</span>
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsBrandsActive) active @endif"
                href="{{ route('catalogs.brands.index') }}"
                data-tooltip="Marcas"
                @if ($catalogsBrandsActive) aria-current="page" @endif
            >
                <i class="bi bi-tag nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Marcas</span>
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsLocationsActive) active @endif"
                href="{{ route('catalogs.locations.index') }}"
                data-tooltip="Ubicaciones"
                @if ($catalogsLocationsActive) aria-current="page" @endif
            >
                <i class="bi bi-geo-alt nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Ubicaciones</span>
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link @if ($catalogsSuppliersActive) active @endif"
                href="{{ route('catalogs.suppliers.index') }}"
                data-tooltip="Proveedores"
                @if ($catalogsSuppliersActive) aria-current="page" @endif
            >
                <i class="bi bi-truck nav-icon" aria-hidden="true"></i>
                <span class="nav-text">Proveedores</span>
            </a>
        </li>
    @endcan

    @if ($showAdminSection)
        <li class="sidebar-divider px-2">
            <hr class="my-1 border-secondary opacity-25" />
        </li>

        <li class="sidebar-section px-2">
            <div class="text-uppercase small text-secondary-emphasis">Administración</div>
        </li>

        @can('users.manage')
            <li class="nav-item">
                <a
                    class="nav-link @if ($adminUsersActive) active @endif"
                    href="{{ route('admin.users.index') }}"
                    data-tooltip="Usuarios"
                    @if ($adminUsersActive) aria-current="page" @endif
                >
                    <i class="bi bi-people nav-icon" aria-hidden="true"></i>
                    <span class="nav-text">Usuarios</span>
                </a>
            </li>
        @endcan

        @can('admin-only')
            <li class="nav-item">
                <a
                    class="nav-link @if ($adminTrashActive) active @endif"
                    href="{{ route('admin.trash.index') }}"
                    data-tooltip="Papelera"
                    @if ($adminTrashActive) aria-current="page" @endif
                >
                    <i class="bi bi-trash nav-icon" aria-hidden="true"></i>
                    <span class="nav-text">Papelera</span>
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link @if ($adminErrorReportsActive) active @endif"
                    href="{{ route('admin.error-reports.lookup') }}"
                    data-tooltip="Errores"
                    @if ($adminErrorReportsActive) aria-current="page" @endif
                >
                    <i class="bi bi-exclamation-triangle nav-icon" aria-hidden="true"></i>
                    <span class="nav-text">Errores (soporte)</span>
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link @if ($catalogsTrashActive) active @endif"
                    href="{{ route('catalogs.trash.index') }}"
                    data-tooltip="Papelera catálogos"
                    @if ($catalogsTrashActive) aria-current="page" @endif
                >
                    <i class="bi bi-trash3 nav-icon" aria-hidden="true"></i>
                    <span class="nav-text">Papelera catálogos</span>
                </a>
            </li>
        @endcan
    @endif
</ul>
