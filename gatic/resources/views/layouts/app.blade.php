<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GATIC') }}</title>

    @php
        $uiPreferences = [];
        if (auth()->check()) {
            $uiPreferences = app(\App\Support\Settings\UserSettingsStore::class)
                ->getBootstrapPreferencesForUser((int) auth()->id());
        }
    @endphp

    {{-- UI preferences bootstrapper (hydrates localStorage + prevents theme flash before Vite loads) --}}
    <script>
        window.gaticUserPrefs = @json($uiPreferences);

        (() => {
            try {
                const prefs = window.gaticUserPrefs && typeof window.gaticUserPrefs === 'object'
                    ? window.gaticUserPrefs
                    : {};

                if (prefs.theme === 'light' || prefs.theme === 'dark') {
                    localStorage.setItem('gatic:theme', prefs.theme);
                }

                if (prefs.density === 'normal' || prefs.density === 'compact') {
                    localStorage.setItem('gatic-density-mode', prefs.density);
                }

                if (typeof prefs.sidebarCollapsed === 'boolean') {
                    localStorage.setItem('gatic-sidebar-collapsed', prefs.sidebarCollapsed ? 'true' : 'false');
                }

                if (prefs.columns && typeof prefs.columns === 'object') {
                    Object.entries(prefs.columns).forEach(([tableKey, hiddenColumns]) => {
                        if (!Array.isArray(hiddenColumns)) {
                            return;
                        }

                        localStorage.setItem(`gatic:columns:${tableKey}`, JSON.stringify(hiddenColumns));
                    });
                }
            } catch {
                // ignore (no localStorage access)
            }

            try {
                const stored = localStorage.getItem('gatic:theme');
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
    <livewire:movements.undo-manager :key="'undo-manager'" />
    <x-ui.hotkeys-help />
    <livewire:ui.command-palette />

    @livewireScripts
    @stack('scripts')
</body>
</html>
