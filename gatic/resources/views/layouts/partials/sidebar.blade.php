@php($appName = config('app.name', 'GATIC'))

<aside class="app-sidebar d-none d-md-flex flex-column py-1 px-2 border-end" data-testid="app-sidebar">
    <div class="app-sidebar-header mb-2">
        <a class="app-sidebar-brand text-decoration-none" href="{{ route('dashboard') }}">
            <span class="fw-semibold">{{ $appName }}</span>
        </a>
        <button
            type="button"
            class="app-sidebar-toggle"
            data-sidebar-toggle
            aria-expanded="true"
            aria-label="Colapsar sidebar"
            title="Colapsar sidebar"
        >
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>
    </div>

    <div class="app-sidebar-body">
        @include('layouts.partials.sidebar-nav')
    </div>
</aside>

<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="appSidebarOffcanvas" aria-labelledby="appSidebarOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="appSidebarOffcanvasLabel">{{ $appName }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        @include('layouts.partials.sidebar-nav')
    </div>
</div>
