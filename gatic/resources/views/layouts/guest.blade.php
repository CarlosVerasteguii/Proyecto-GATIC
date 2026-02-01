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
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @php($runningPhpUnit = class_exists(\PHPUnit\Framework\TestCase::class, false))
    @unless($runningPhpUnit)
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endunless

    @livewireStyles
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'GATIC') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Alternar navegaci&oacute;n">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">

                    </ul>
                </div>
            </div>
        </nav>        
        <main class="py-4">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <x-ui.toast-container />

    @livewireScripts
</body>
</html>
