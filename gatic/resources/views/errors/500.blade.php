@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Error inesperado</div>

                <div class="card-body">
                    <x-ui.error-alert-with-id
                        message="Ocurrió un error inesperado. Si el problema persiste, comparte este ID con TI."
                        :error-id="$errorId ?? null"
                    />

                    <a
                        class="btn btn-primary"
                        href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    >
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

