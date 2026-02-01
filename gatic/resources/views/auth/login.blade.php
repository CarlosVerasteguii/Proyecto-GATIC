<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Iniciar sesión - {{ config('app.name', 'GATIC') }}</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    @php($runningPhpUnit = class_exists(\PHPUnit\Framework\TestCase::class, false))
    @unless($runningPhpUnit)
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endunless
</head>
<body>
    <div class="login-page">
        <!-- Background slideshow -->
        <div class="login-bg-slideshow" aria-hidden="true">
            <div class="login-bg-slide active"></div>
            <div class="login-bg-slide"></div>
            <div class="login-bg-slide"></div>
            <div class="login-bg-slide"></div>
            <div class="login-bg-slide"></div>
            <div class="login-bg-slide"></div>
            <div class="login-bg-slide"></div>
        </div>
        <main class="login-container" role="main" aria-labelledby="login-title">
            <div class="login-card">
                <header class="login-header">
                    <h1 id="login-title" class="login-logo">GATIC</h1>
                    <p class="login-subtitle">Sistema de Gestión de Activos TI</p>
                </header>

                <div class="login-body">
                    @if (session('status'))
                        <div class="alert alert-success login-alert" role="alert">
                            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger login-alert" role="alert" aria-live="polite">
                            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i>
                            @foreach ($errors->all() as $error)
                                {{ $error }}@if(!$loop->last)<br>@endif
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="login-form" aria-label="Formulario de inicio de sesión" id="login-form">
                        @csrf

                        <div class="form-floating position-relative">
                            <i class="bi bi-envelope input-icon" aria-hidden="true"></i>
                            <input
                                id="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="correo@ejemplo.com"
                                required
                                autocomplete="email"
                                autofocus
                                aria-describedby="email-error"
                                aria-invalid="@error('email')true@else false @enderror"
                            >
                            <label for="email">Correo electrónico</label>
                            @error('email')
                                <div class="invalid-feedback" id="email-error" role="alert">
                                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-floating position-relative">
                            <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                            <input
                                id="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                placeholder="Contraseña"
                                required
                                autocomplete="current-password"
                                aria-describedby="password-error"
                                aria-invalid="@error('password')true@else false @enderror"
                            >
                            <label for="password">Contraseña</label>
                            @error('password')
                                <div class="invalid-feedback" id="password-error" role="alert">
                                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="remember"
                                id="remember"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="remember">
                                Mantener sesión iniciada
                                <small class="d-block text-muted">
                                    No usar en equipos compartidos
                                </small>
                            </label>
                        </div>

                        <button type="submit" class="btn login-btn" id="login-btn" aria-label="Iniciar sesión en GATIC">
                            <span class="btn-content">
                                <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                                <span>Iniciar sesión</span>
                            </span>
                            <span class="btn-spinner d-none" aria-live="polite">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span>Iniciando sesión...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>

        <footer class="login-footer">
            <div class="login-attribution">
                <span>Imagen:</span>
                <a href="https://www.cfe.mx" target="_blank" rel="noopener">CFE</a>
                <span class="cc-icons">
                    <a href="https://creativecommons.org/licenses/by-nc/4.0/" target="_blank" rel="noopener" title="CC BY-NC 4.0" aria-label="Creative Commons Atribución-NoComercial 4.0">
                        <i class="bi bi-cc-circle" aria-hidden="true"></i>
                    </a>
                </span>
                <span>CC BY-NC 4.0</span>
            </div>
        </footer>
    </div>

    <script>
        // Loading state en el botón
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const btn = document.getElementById('login-btn');
            const btnContent = btn.querySelector('.btn-content');
            const btnSpinner = btn.querySelector('.btn-spinner');

            // Prevenir múltiples envíos
            if (btn.disabled) {
                e.preventDefault();
                return;
            }

            btn.disabled = true;
            btnContent.classList.add('d-none');
            btnSpinner.classList.remove('d-none');
        });

        // Background slideshow - Ken Burns effect
        (function() {
            const slides = document.querySelectorAll('.login-bg-slide');
            let currentSlide = 0;
            const SLIDE_DURATION = 8000; // 8 segundos por imagen

            function nextSlide() {
                // Remover active del slide actual
                slides[currentSlide].classList.remove('active');

                // Avanzar al siguiente slide
                currentSlide = (currentSlide + 1) % slides.length;

                // Activar el nuevo slide (esto dispara la transición CSS)
                slides[currentSlide].classList.add('active');
            }

            // Cambiar de imagen cada 8 segundos
            setInterval(nextSlide, SLIDE_DURATION);
        })();
    </script>
</body>
</html>
