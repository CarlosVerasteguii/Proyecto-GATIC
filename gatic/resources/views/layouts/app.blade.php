<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GATIC') }}</title>

    {{-- Theme bootstrapper (prevents flash before Vite loads) --}}
    <script>
        (() => {
            try {
                const STORAGE_KEY = 'gatic:theme';
                const stored = localStorage.getItem(STORAGE_KEY);
                const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches === true;
                const theme = stored === 'dark' || stored === 'light' ? stored : (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch {
                // ignore (no localStorage access)
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @php($runningPhpUnit = class_exists(\PHPUnit\Framework\TestCase::class, false))
    @unless($runningPhpUnit)
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endunless

    @livewireStyles
    @stack('styles')
</head>
<body>
    <div id="app" class="app-shell">
        @include('layouts.navigation')

        <main class="app-main py-4">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <x-ui.toast-container />
    <x-ui.hotkeys-help />
    <livewire:ui.command-palette />

    @livewireScripts
    @stack('scripts')
</body>
</html>
