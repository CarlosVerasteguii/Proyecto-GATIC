<div class="container position-relative">
    @php
        $assets = $contract->assets;
        $totalAssets = (int) ($contract->assets_count ?? $assets->count());
        $availableAssets = $assets->where('status', \App\Models\Asset::STATUS_AVAILABLE)->count();
        $typeTone = $contract->type === \App\Models\Contract::TYPE_PURCHASE ? 'info' : 'warning';
        $vigencyLabel = $contract->start_date || $contract->end_date
            ? trim(($contract->start_date?->format('d/m/Y') ?? 'Sin inicio').' al '.($contract->end_date?->format('d/m/Y') ?? 'sin fin'))
            : 'Sin fechas definidas';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header :title="$contract->identifier" :subtitle="$contract->supplier?->name ?? 'Sin proveedor asignado'">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Contratos', 'url' => auth()->user()?->can('inventory.manage') ? route('inventory.contracts.index') : null],
                        ['label' => $contract->identifier, 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.badge :tone="$typeTone" variant="compact" :with-rail="false">
                        {{ $contract->type_label }}
                    </x-ui.badge>
                    @if ($contract->supplier?->name)
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            {{ $contract->supplier->name }}
                        </x-ui.badge>
                    @endif
                </x-slot:status>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Activos vinculados" :value="$totalAssets" />
                    <x-ui.detail-header-kpi label="Disponibles" :value="$availableAssets" variant="success" />
                </x-slot:kpis>

                <x-slot:actions>
                    @if (auth()->user()?->can('inventory.manage'))
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.contracts.index') }}">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Volver
                        </a>
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.contracts.edit', ['contract' => $contract->id]) }}">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                            Editar
                        </a>
                    @endif
                </x-slot:actions>
            </x-ui.detail-header>

            <x-ui.section-card
                title="Resumen del contrato"
                subtitle="Contexto general para revisar vigencia, proveedor y alcance operativo."
                icon="bi-journal-text"
                class="mb-4"
            >
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Identificador</div>
                            <div class="fw-semibold mt-2">{{ $contract->identifier }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Tipo</div>
                            <div class="mt-2">
                                <x-ui.badge :tone="$typeTone" variant="compact" :with-rail="false">
                                    {{ $contract->type_label }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Proveedor</div>
                            <div class="fw-semibold mt-2">{{ $contract->supplier?->name ?? 'Sin proveedor asignado' }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Vigencia</div>
                            <div class="fw-semibold mt-2">{{ $vigencyLabel }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                            <div class="small text-body-secondary text-uppercase fw-semibold">Notas</div>
                            <div class="mt-2 text-body-secondary">
                                {{ $contract->notes ?: 'Sin notas operativas registradas.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            <x-ui.section-card
                title="Activos vinculados"
                subtitle="Consulta los activos asociados al contrato y navega a su detalle cuando necesites más contexto."
                icon="bi-hdd-stack"
                bodyClass="p-0"
            >
                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        {{ number_format($totalAssets) }}
                    </x-ui.badge>
                </x-slot:actions>

                @if ($totalAssets === 0)
                    <div class="p-4">
                        <x-ui.empty-state
                            icon="bi-link-45deg"
                            title="Sin activos vinculados"
                            description="Este contrato todavía no tiene activos asociados."
                            compact
                        />
                    </div>
                @else
                    <div class="table-responsive-xl">
                        <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                            <thead>
                                <tr>
                                    <th>Activo</th>
                                    <th>Identificadores</th>
                                    <th>Estado</th>
                                    <th>Ubicación</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assets as $asset)
                                    <tr wire:key="contract-asset-{{ $asset->id }}">
                                        <td class="min-w-0">
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $asset->product?->name ?? 'Producto no disponible' }}</div>
                                                <div class="small text-body-secondary">Activo ID {{ $asset->id }}</div>
                                            </div>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="fw-semibold">{{ $asset->serial }}</div>
                                            <div class="small text-body-secondary">
                                                Asset tag: {{ $asset->asset_tag ?? '—' }}
                                            </div>
                                        </td>
                                        <td class="text-nowrap">
                                            <x-ui.status-badge :status="$asset->status" />
                                        </td>
                                        <td>{{ $asset->location?->name ?? 'Sin ubicación' }}</td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver activo
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.section-card>
        </div>
    </div>
</div>
