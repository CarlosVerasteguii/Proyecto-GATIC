<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'GATIC') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="bg-body-tertiary">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h1 class="h4 mb-2">{{ config('app.name', 'GATIC') }}</h1>
                            <p class="text-body-secondary mb-4">
                                Sistema interno de inventario (GATIC). Inicia sesi&oacute;n para continuar.
                            </p>

                            <div class="d-flex gap-2">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al dashboard</a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesi&oacute;n</a>
                                @endauth
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </body>
</html>
