<nav class="app-topbar navbar navbar-expand-md navbar-dark bg-primary shadow-sm" data-testid="app-topbar">
    <div class="container-fluid">
        <button
            class="navbar-toggler d-md-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#appSidebarOffcanvas"
            aria-controls="appSidebarOffcanvas"
            aria-label="Abrir men&uacute;"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <a class="navbar-brand ms-2" href="{{ route('dashboard') }}">
            {{ config('app.name', 'GATIC') }}
        </a>

        @can('inventory.view')
            <form
                class="d-none d-md-flex ms-3 app-topbar-search"
                action="{{ route('inventory.search') }}"
                method="GET"
                role="search"
                data-testid="global-search-form"
            >
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input
                        type="search"
                        name="q"
                        class="form-control"
                        value="{{ request()->query('q') }}"
                        placeholder="Buscar inventario (/)…"
                        aria-label="Buscar en inventario"
                        autocomplete="off"
                        data-global-search="true"
                    />
                </div>
            </form>
        @endcan

        <div class="ms-auto d-flex align-items-center">
            @can('inventory.view')
                <a
                    class="btn btn-sm btn-outline-light d-md-none me-2"
                    href="{{ route('inventory.search') }}"
                    aria-label="Buscar en inventario"
                >
                    <i class="bi bi-search" aria-hidden="true"></i>
                </a>
            @endcan
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <button
                        id="navbarUserDropdown"
                        class="nav-link dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        {{ Auth::user()->name }}
                    </button>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                        {{-- MVP: Profile link deshabilitado - Story 1.3 scope = "solo login/logout" --}}
                        {{-- <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            {{ __('Profile') }}
                        </a> --}}
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item">Cerrar sesi&oacute;n</button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
