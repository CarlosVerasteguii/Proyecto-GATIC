{{--
    Detail Header Component
    -----------------------
    Consistent header for detail views (Product/Asset) with title, status, KPIs, and actions.

    Usage:
        <x-ui.detail-header
            :title="$product->name"
            subtitle="Laptop corporativa"
        >
            <x-slot:status>
                <x-ui.status-badge :status="$asset->status" />
            </x-slot:status>

            <x-slot:kpis>
                <x-ui.detail-header-kpi label="Total" :value="$total" />
                <x-ui.detail-header-kpi label="Disponibles" :value="$available" />
            </x-slot:kpis>

            <x-slot:breadcrumb>
                <a href="..." class="text-muted text-decoration-none">Volver</a>
            </x-slot:breadcrumb>

            <x-slot:actions>
                <a class="btn btn-primary btn-sm">Acción principal</a>
                <a class="btn btn-outline-secondary btn-sm">Secundaria</a>
            </x-slot:actions>
        </x-ui.detail-header>

    Props:
        - title (string): Main entity name/identifier
        - subtitle (string, optional): Secondary description
--}}
@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'detail-header mb-4']) }}>
    {{-- Breadcrumb / Navigation row --}}
    @if(isset($breadcrumbs) || isset($breadcrumb))
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                {{ $breadcrumbs ?? $breadcrumb }}
            </div>
            @if(isset($actions))
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{-- Main header card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                {{-- Title + Status column --}}
                <div class="col-12 col-lg-auto flex-grow-1">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h1 class="h4 mb-0 fw-semibold">{{ $title }}</h1>
                            @if(isset($status))
                                {{ $status }}
                            @endif
                        </div>
                        @if($subtitle)
                            <span class="text-muted small">{{ $subtitle }}</span>
                        @endif
                    </div>
                </div>

                {{-- KPIs column --}}
                @if(isset($kpis))
                    <div class="col-12 col-lg-auto">
                        <div class="d-flex gap-4 flex-wrap">
                            {{ $kpis }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions row (alternative placement if no breadcrumb) --}}
    @if(isset($actions) && !isset($breadcrumbs) && !isset($breadcrumb))
        <div class="d-flex gap-2 justify-content-end mt-3 flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>
