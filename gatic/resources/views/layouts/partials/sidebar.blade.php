@php($appName = config('app.name', 'GATIC'))

<aside class="app-sidebar d-none d-md-flex flex-column p-3 border-end" data-testid="app-sidebar">
    <a class="app-sidebar-brand text-decoration-none mb-3" href="{{ route('dashboard') }}">
        <span class="fw-semibold">{{ $appName }}</span>
    </a>

    @include('layouts.partials.sidebar-nav')
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
