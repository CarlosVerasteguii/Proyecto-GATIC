@php
    $currentUser = auth()->user();
    $adminUsersActive = request()->routeIs('admin.users.*');
    $adminTrashActive = request()->routeIs('admin.trash.*');
    $adminErrorReportsActive = request()->routeIs('admin.error-reports.*');
    $adminSettingsActive = request()->routeIs('admin.settings.*');
    $employeesActive = request()->routeIs('employees.*');
    $pendingTasksActive = request()->routeIs('pending-tasks.*');
    $catalogsCategoriesActive = request()->routeIs('catalogs.categories.*');
    $catalogsBrandsActive = request()->routeIs('catalogs.brands.*');
    $catalogsLocationsActive = request()->routeIs('catalogs.locations.*');
    $catalogsSuppliersActive = request()->routeIs('catalogs.suppliers.*');
    $catalogsTrashActive = request()->routeIs('catalogs.trash.*');
    $inventoryAssetsActive = request()->routeIs('inventory.assets.*');
    $inventoryProductsActive = request()->routeIs('inventory.products.*');
    $inventoryContractsActive = request()->routeIs('inventory.contracts.*');
    $showInventorySection = $currentUser?->can('inventory.view') ?? false;
    $showOperationsSection = $currentUser?->can('inventory.manage') ?? false;
    $showCatalogsSection = $currentUser?->can('catalogs.manage') ?? false;
    $showAdminSection = ($currentUser?->can('users.manage') ?? false)
        || ($currentUser?->can('admin-only') ?? false);

    $sections = array_values(array_filter([
        $showInventorySection ? [
            'label' => 'Inventario',
            'items' => array_values(array_filter([
                [
                    'label' => 'Productos',
                    'route' => route('inventory.products.index'),
                    'icon' => 'bi bi-box-seam',
                    'active' => $inventoryProductsActive,
                ],
                [
                    'label' => 'Activos',
                    'route' => route('inventory.assets.index'),
                    'icon' => 'bi bi-hdd',
                    'active' => $inventoryAssetsActive,
                ],
                $showOperationsSection ? [
                    'label' => 'Contratos',
                    'route' => route('inventory.contracts.index'),
                    'icon' => 'bi bi-file-earmark-text',
                    'active' => $inventoryContractsActive,
                ] : null,
            ])),
        ] : null,
        $showOperationsSection ? [
            'label' => 'Operaciones',
            'items' => [
                [
                    'label' => 'Tareas Pendientes',
                    'route' => route('pending-tasks.index'),
                    'icon' => 'bi bi-list-task',
                    'active' => $pendingTasksActive,
                ],
                [
                    'label' => 'Empleados',
                    'route' => route('employees.index'),
                    'icon' => 'bi bi-person-badge',
                    'active' => $employeesActive,
                ],
            ],
        ] : null,
        $showCatalogsSection ? [
            'label' => 'Catálogos',
            'items' => [
                [
                    'label' => 'Categorías',
                    'route' => route('catalogs.categories.index'),
                    'icon' => 'bi bi-folder',
                    'active' => $catalogsCategoriesActive,
                ],
                [
                    'label' => 'Marcas',
                    'route' => route('catalogs.brands.index'),
                    'icon' => 'bi bi-tag',
                    'active' => $catalogsBrandsActive,
                ],
                [
                    'label' => 'Ubicaciones',
                    'route' => route('catalogs.locations.index'),
                    'icon' => 'bi bi-geo-alt',
                    'active' => $catalogsLocationsActive,
                ],
                [
                    'label' => 'Proveedores',
                    'route' => route('catalogs.suppliers.index'),
                    'icon' => 'bi bi-truck',
                    'active' => $catalogsSuppliersActive,
                ],
            ],
        ] : null,
        $showAdminSection ? [
            'label' => 'Administración',
            'items' => array_values(array_filter([
                ($currentUser?->can('users.manage') ?? false) ? [
                    'label' => 'Usuarios',
                    'route' => route('admin.users.index'),
                    'icon' => 'bi bi-people',
                    'active' => $adminUsersActive,
                ] : null,
                ($currentUser?->can('admin-only') ?? false) ? [
                    'label' => 'Papelera',
                    'route' => route('admin.trash.index'),
                    'icon' => 'bi bi-trash',
                    'active' => $adminTrashActive,
                ] : null,
                ($currentUser?->can('admin-only') ?? false) ? [
                    'label' => 'Errores (soporte)',
                    'route' => route('admin.error-reports.lookup'),
                    'icon' => 'bi bi-exclamation-triangle',
                    'active' => $adminErrorReportsActive,
                ] : null,
                ($currentUser?->can('admin-only') ?? false) ? [
                    'label' => 'Papelera catálogos',
                    'route' => route('catalogs.trash.index'),
                    'icon' => 'bi bi-trash3',
                    'active' => $catalogsTrashActive,
                ] : null,
                ($currentUser?->can('admin-only') ?? false) ? [
                    'label' => 'Configuración',
                    'route' => route('admin.settings.index'),
                    'icon' => 'bi bi-gear',
                    'active' => $adminSettingsActive,
                ] : null,
            ])),
        ] : null,
    ]));
@endphp

<ul
    @if (isset($navId)) id="{{ $navId }}" @endif
    class="sidebar-nav nav nav-pills flex-column"
    aria-label="Navegación principal"
>
    @foreach ($sections as $section)
        @if (! $loop->first)
            <li class="sidebar-divider px-2" aria-hidden="true">
                <hr />
            </li>
        @endif

        <li class="sidebar-section px-2">
            <span class="sidebar-group-label">{{ $section['label'] }}</span>
        </li>

        @foreach ($section['items'] as $item)
            <li class="nav-item">
                <a
                    class="nav-link sidebar-link @if ($item['active']) active @endif"
                    href="{{ $item['route'] }}"
                    title="{{ $item['label'] }}"
                    aria-label="{{ $item['label'] }}"
                    @if ($item['active']) aria-current="page" @endif
                >
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <i class="{{ $item['icon'] }} nav-icon" aria-hidden="true"></i>
                    </span>
                    <span class="sidebar-link__body">
                        <span class="nav-text">{{ $item['label'] }}</span>
                    </span>
                </a>
            </li>
        @endforeach
    @endforeach
</ul>
