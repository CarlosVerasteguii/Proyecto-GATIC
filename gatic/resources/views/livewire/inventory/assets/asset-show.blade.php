<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );

        $backUrl = is_string($returnTo) && $returnTo !== ''
            ? $returnTo
            : route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery);

        $backLabel = 'Volver a activos';

        if (str_starts_with($backUrl, '/inventory/search')) {
            $backLabel = 'Volver a búsqueda';
        } elseif (str_starts_with($backUrl, '/inventory/assets')) {
            $backLabel = 'Volver a activos globales';
        }
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header :title="$asset->serial" :subtitle="$product->name">
                <x-slot:breadcrumbs>
                    @if (is_string($returnTo) && $returnTo !== '')
                        @php
                            $returnToLabel = 'Regresar';

                            if (str_starts_with($returnTo, '/inventory/search')) {
                                $returnToLabel = 'Búsqueda';
                            } elseif (str_starts_with($returnTo, '/inventory/assets')) {
                                $returnToLabel = 'Activos';
                            }
                        @endphp
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => $returnToLabel, 'url' => $returnTo],
                            ['label' => $asset->serial, 'url' => null],
                        ]" />
                    @else
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Productos', 'url' => route('inventory.products.index', $returnQuery)],
                            ['label' => $product->name, 'url' => route('inventory.products.show', ['product' => $product->id] + $returnQuery)],
                            ['label' => 'Activos', 'url' => route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery)],
                            ['label' => $asset->serial, 'url' => null],
                        ]" />
                    @endif
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.status-badge :status="$asset->status" solid />
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        {{ $asset->location?->name ?? 'Sin ubicación' }}
                    </x-ui.badge>
                    @if ($asset->asset_tag)
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Asset tag {{ $asset->asset_tag }}
                        </x-ui.badge>
                    @endif
                    @foreach ($statusHighlights as $highlight)
                        <x-ui.badge :tone="$highlight['tone']" variant="compact" :with-rail="false">
                            {{ $highlight['label'] }}
                        </x-ui.badge>
                    @endforeach
                </x-slot:status>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Movimientos" :value="$headerCounts['movements']" />
                    <x-ui.detail-header-kpi label="Notas" :value="$headerCounts['notes']" variant="info" />
                    @if ($headerCounts['attachments'] !== null)
                        <x-ui.detail-header-kpi label="Adjuntos" :value="$headerCounts['attachments']" variant="warning" />
                    @endif
                </x-slot:kpis>

                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $backUrl }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        {{ $backLabel }}
                    </a>

                    @can('inventory.manage')
                        @if (\App\Support\Assets\AssetStatusTransitions::canAssign($asset->status))
                            <a class="btn btn-sm btn-success" href="{{ route('inventory.products.assets.assign', ['product' => $product->id, 'asset' => $asset->id] + (is_string($returnTo) && $returnTo !== '' ? ['returnTo' => $returnTo] : [])) }}">
                                <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                                Asignar
                            </a>
                        @endif

                        @if (\App\Support\Assets\AssetStatusTransitions::canLoan($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.loan', ['product' => $product->id, 'asset' => $asset->id] + (is_string($returnTo) && $returnTo !== '' ? ['returnTo' => $returnTo] : [])) }}">
                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                Prestar
                            </a>
                        @endif

                        @if (\App\Support\Assets\AssetStatusTransitions::canReturn($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.return', ['product' => $product->id, 'asset' => $asset->id] + (is_string($returnTo) && $returnTo !== '' ? ['returnTo' => $returnTo] : [])) }}">
                                <i class="bi bi-arrow-return-left me-1" aria-hidden="true"></i>
                                Devolver
                            </a>
                        @endif

                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.assets.edit', ['product' => $product->id, 'asset' => $asset->id]) }}">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                            Editar
                        </a>
                    @endcan

                    @can('admin-only')
                        <a class="btn btn-sm btn-warning" href="{{ route('inventory.products.assets.adjust', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                            Ajustar
                        </a>
                    @endcan
                </x-slot:actions>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Resumen del activo"
                subtitle="Contexto operativo para ubicar responsable, producto y cobertura sin recorrer toda la pantalla."
                icon="bi-hdd-stack"
                class="mb-4"
            >
                <div class="row g-3">
                    @foreach ($overviewCards as $card)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $card['label'] }}</div>
                                @if ($card['href'])
                                    <a href="{{ $card['href'] }}" class="fw-semibold mt-2 d-inline-block text-decoration-none">
                                        {{ $card['value'] }}
                                    </a>
                                @else
                                    <div class="fw-semibold mt-2">{{ $card['value'] }}</div>
                                @endif

                                @if ($card['badge'])
                                    <div class="mt-2">
                                        <x-ui.badge :tone="$card['badge']['tone']" variant="compact" :with-rail="false">
                                            {{ $card['badge']['label'] }}
                                        </x-ui.badge>
                                    </div>
                                @endif

                                <div class="small text-body-secondary mt-2">
                                    {{ $card['description'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>

            <x-ui.section-card
                title="Cobertura y ciclo de vida"
                subtitle="Reúne costo, vida útil, renovación y garantía con el mismo lenguaje visual del resto del sistema."
                icon="bi-shield-check"
                class="mb-4"
            >
                <div class="row g-3">
                    @foreach ($lifecycleCards as $card)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $card['label'] }}</div>
                                <div class="fw-semibold mt-2">{{ $card['value'] }}</div>

                                @if ($card['badge'])
                                    <div class="mt-2">
                                        <x-ui.badge :tone="$card['badge']['tone']" variant="compact" :with-rail="false">
                                            {{ $card['badge']['label'] }}
                                        </x-ui.badge>
                                    </div>
                                @endif

                                <div class="small text-body-secondary mt-2">
                                    {{ $card['description'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>

            <div class="mb-3">
                <h2 class="h5 mb-1">Trazabilidad</h2>
                <p class="small text-body-secondary mb-0">
                    Revisa historial, notas y adjuntos sin perder el resumen operativo del activo.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <livewire:ui.timeline-panel
                        :entity-type="\App\Models\Asset::class"
                        :entity-id="$asset->id"
                    />
                </div>

                <div class="col-12 col-xl-5">
                    <livewire:ui.notes-panel
                        :noteable-type="\App\Models\Asset::class"
                        :noteable-id="$asset->id"
                    />

                    @can('attachments.view')
                        <livewire:ui.attachments-panel
                            :attachable-type="\App\Models\Asset::class"
                            :attachable-id="$asset->id"
                        />
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
