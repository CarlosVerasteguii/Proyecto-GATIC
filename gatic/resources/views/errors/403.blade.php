@extends('layouts.guest')

@section('content')
<div class="container guest-view">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7 col-xl-6">
            <section class="guest-surface" aria-labelledby="error-403-title">
                <div class="guest-surface__header">
                    <div class="guest-surface__eyebrow">
                        <i class="bi bi-slash-circle" aria-hidden="true"></i>
                        Error 403
                    </div>
                    <h1 class="guest-surface__title" id="error-403-title">Acceso restringido</h1>
                    <p class="guest-surface__subtitle">
                        No tienes permisos para entrar a este recurso con tu perfil actual. Si necesitas acceso, solicita la revisión a TI o a un administrador.
                    </p>
                </div>

                <div class="guest-surface__actions">
                    <a
                        class="btn btn-primary"
                        href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    >
                        {{ auth()->check() ? 'Volver al panel' : 'Ir al inicio de sesión' }}
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
