<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GATIC') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

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

    @livewireScripts
    @stack('scripts')
</body>
</html>
