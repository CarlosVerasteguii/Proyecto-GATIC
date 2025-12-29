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

        <div class="ms-auto">
            <ul class="navbar-nav ms-auto">
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
