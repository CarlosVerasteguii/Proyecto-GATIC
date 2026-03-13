@extends('layouts.guest')

@section('content')
<div class="container guest-view">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7 col-xl-6">
            <section class="guest-surface" aria-labelledby="confirm-password-title">
                <div class="guest-surface__header">
                    <div class="guest-surface__eyebrow">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        Área segura
                    </div>
                    <h1 class="guest-surface__title" id="confirm-password-title">Confirma tu contraseña</h1>
                    <p class="guest-surface__subtitle">
                        Antes de continuar con esta acción, valida tu identidad con la contraseña actual de tu cuenta.
                    </p>
                </div>

                <div class="guest-surface__body">
                    <form method="POST" action="{{ route('password.confirm') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label for="password" class="form-label">Contraseña actual</label>
                            <input
                                id="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                required
                                autocomplete="current-password"
                                autofocus
                            >

                            @error('password')
                                <div class="invalid-feedback d-block" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                Confirmar y continuar
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
