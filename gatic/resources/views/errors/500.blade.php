@extends('layouts.guest')

@section('content')
<div class="container guest-view">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7 col-xl-6">
            <section class="guest-surface" aria-labelledby="error-500-title">
                <div class="guest-surface__header">
                    <div class="guest-surface__eyebrow guest-surface__eyebrow--danger">
                        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
                        Error 500
                    </div>
                    <h1 class="guest-surface__title" id="error-500-title">Error inesperado</h1>
                    <p class="guest-surface__subtitle">
                        La operación no pudo completarse. Comparte el identificador con TI si vuelve a ocurrir para facilitar el diagnóstico.
                    </p>
                </div>

                <div class="guest-surface__body">
                    <x-ui.error-alert-with-id
                        message="Ocurrió un error inesperado. Si el problema persiste, comparte este ID con TI."
                        :error-id="$errorId ?? null"
                    />
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
