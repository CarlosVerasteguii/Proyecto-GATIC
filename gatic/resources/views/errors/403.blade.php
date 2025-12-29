@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">No autorizado (403)</div>

                <div class="card-body">
                    <p class="mb-3">
                        No tienes permisos para acceder a este recurso.
                    </p>

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

