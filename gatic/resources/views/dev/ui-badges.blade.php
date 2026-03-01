@extends('layouts.app')

@section('content')
    @php
        use App\Models\Asset;

        $tones = [
            'neutral',
            'secondary',
            'info',
            'success',
            'warning',
            'danger',
            'primary',
            'role-admin',
            'role-editor',
            'role-lector',
            'status-available',
            'status-loaned',
            'status-assigned',
            'status-pending',
            'status-retired',
        ];
    @endphp

    <div class="container-fluid">
        <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">Badges — Paleta B (Rail)</h1>
                <div class="text-muted small">
                    Contrato visual para <code>&lt;x-ui.badge&gt;</code> (Blade) y wrappers de compatibilidad.
                </div>
            </div>

            <div class="text-muted small">GET <code>/dev/ui-badges</code></div>
        </div>

        <div class="row g-3">
            @foreach (['light' => 'light', 'dark' => 'dark'] as $label => $theme)
                <div class="col-12 col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <strong class="mb-0 text-capitalize">{{ $label }}</strong>
                            <span class="small text-muted">data-bs-theme="{{ $theme }}"</span>
                        </div>

                        <div class="card-body" data-bs-theme="{{ $theme }}">
                            <h2 class="h6 mb-2">Tonos y variantes</h2>

                            <div class="d-flex flex-column gap-3">
                                @foreach ($tones as $tone)
                                    <section>
                                        <div class="small text-muted mb-2">
                                            <code>{{ $tone }}</code>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <x-ui.badge :tone="$tone">Default</x-ui.badge>
                                            <x-ui.badge :tone="$tone" variant="compact">Compact</x-ui.badge>
                                            <x-ui.badge :tone="$tone" variant="solid">Solid</x-ui.badge>
                                        </div>
                                    </section>
                                @endforeach

                                <hr class="my-1">

                                <section>
                                    <h2 class="h6 mb-2">Opciones</h2>

                                    <div class="d-flex flex-wrap gap-2">
                                        <x-ui.badge tone="neutral" :withRail="false">Sin rail</x-ui.badge>
                                        <x-ui.badge tone="info" icon="bi-info-circle-fill">Con icono</x-ui.badge>
                                        <x-ui.badge tone="success" as="a" href="#">Interactivo (link)</x-ui.badge>
                                        <x-ui.badge tone="warning" as="button">Interactivo (button)</x-ui.badge>
                                    </div>
                                </section>

                                <hr class="my-1">

                                <section>
                                    <h2 class="h6 mb-2">Wrapper: <code>&lt;x-ui.status-badge&gt;</code></h2>

                                    <div class="d-flex flex-wrap gap-2">
                                        <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" />
                                        <x-ui.status-badge :status="Asset::STATUS_ASSIGNED" />
                                        <x-ui.status-badge :status="Asset::STATUS_LOANED" />
                                        <x-ui.status-badge :status="Asset::STATUS_PENDING_RETIREMENT" />
                                        <x-ui.status-badge :status="Asset::STATUS_RETIRED" />
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" solid />
                                        <x-ui.status-badge :status="Asset::STATUS_ASSIGNED" solid />
                                        <x-ui.status-badge :status="Asset::STATUS_LOANED" solid />
                                        <x-ui.status-badge :status="Asset::STATUS_PENDING_RETIREMENT" solid />
                                        <x-ui.status-badge :status="Asset::STATUS_RETIRED" solid />
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

