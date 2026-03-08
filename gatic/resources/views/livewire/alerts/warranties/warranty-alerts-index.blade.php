<div class="container position-relative">
    <x-ui.long-request />

    @php
        $resultsCount = $alerts->total();
        $hasFilters = $this->hasActiveFilters();
        $modeLabel = $type === 'expired' ? 'Vencidas' : 'Por vencer';
        $modeTone = $type === 'expired' ? 'danger' : 'warning';
        $selectedLocation = $locations->firstWhere('id', $this->locationId);
        $selectedCategory = $categories->firstWhere('id', $this->categoryId);
        $selectedBrand = $brands->firstWhere('id', $this->brandId);
        $activeFiltersCount = collect([$this->locationId, $this->categoryId, $this->brandId])
            ->filter(static fn ($value): bool => $value !== null && $value !== '')
            ->count();
        $emptyTitle = $type === 'expired'
            ? 'Sin garantías vencidas'
            : 'Sin garantías por vencer';
        $emptyDescription = $type === 'expired'
            ? 'No hay garantías vencidas para el alcance seleccionado.'
            : "No hay garantías que venzan dentro de los próximos {$resolvedWindowDays} días.";
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Alertas de garantías"
                subtitle="Supervisa garantías vencidas o próximas a vencer con el mismo contexto del dashboard."
                filterId="warranty-alerts-filters"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Dashboard', 'url' => route('dashboard')],
                        ['label' => 'Alertas de garantías', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Alertas <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>
                    <x-ui.badge :tone="$modeTone" variant="compact" :with-rail="false">
                        {{ $modeLabel }}
                    </x-ui.badge>
                    @if ($type === 'due-soon')
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                            Ventana <strong>{{ $resolvedWindowDays }} días</strong>
                        </x-ui.badge>
                    @endif
                    @if ($activeFiltersCount > 0)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Filtros <strong>{{ $activeFiltersCount }}</strong>
                        </x-ui.badge>
                    @endif

                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Dashboard
                    </a>
                </x-slot:actions>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="warranty-alerts-location" class="form-label">Ubicación</label>
                        <select
                            id="warranty-alerts-location"
                            class="form-select"
                            wire:model.live="locationId"
                            aria-label="Filtrar por ubicación"
                        >
                            <option value="">Todas</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="warranty-alerts-category" class="form-label">Categoría</label>
                        <select
                            id="warranty-alerts-category"
                            class="form-select"
                            wire:model.live="categoryId"
                            aria-label="Filtrar por categoría"
                        >
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="warranty-alerts-brand" class="form-label">Marca</label>
                        <select
                            id="warranty-alerts-brand"
                            class="form-select"
                            wire:model.live="brandId"
                            aria-label="Filtrar por marca"
                        >
                            <option value="">Todas</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($type === 'due-soon')
                        <div class="col-12 col-md-3">
                            <label for="warranty-alerts-window" class="form-label">Ventana</label>
                            <select
                                id="warranty-alerts-window"
                                class="form-select"
                                wire:model.live="windowDays"
                                aria-label="Ventana de días para garantías por vencer"
                            >
                                @foreach ($windowDaysOptions as $days)
                                    <option value="{{ $days }}">{{ $days }} días</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            wire:loading.attr="disabled"
                            wire:target="clearFilters"
                            aria-label="Limpiar filtros de alertas de garantías"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="d-flex flex-column gap-3 mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        <a
                            class="btn btn-sm {{ $type === 'expired' ? 'btn-danger' : 'btn-outline-secondary' }}"
                            href="{{ route('alerts.warranties.index', array_merge(['type' => 'expired'], $filterParams ?? [])) }}"
                        >
                            <i class="bi bi-shield-x me-1" aria-hidden="true"></i>
                            Vencidas
                        </a>
                        <a
                            class="btn btn-sm {{ $type === 'due-soon' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}"
                            href="{{ route('alerts.warranties.index', array_merge(['type' => 'due-soon', 'windowDays' => $resolvedWindowDays], $filterParams ?? [])) }}"
                        >
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                            Por vencer
                        </a>
                    </div>

                    @if ($hasFilters)
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-body-secondary">Filtros activos:</span>
                            @if (is_string($selectedLocation?->name) && $selectedLocation->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Ubicación: {{ $selectedLocation->name }}</x-ui.badge>
                            @endif
                            @if (is_string($selectedCategory?->name) && $selectedCategory->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Categoría: {{ $selectedCategory->name }}</x-ui.badge>
                            @endif
                            @if (is_string($selectedBrand?->name) && $selectedBrand->name !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Marca: {{ $selectedBrand->name }}</x-ui.badge>
                            @endif
                        </div>
                    @endif

                    <div class="small text-body-secondary">
                        @if ($type === 'expired')
                            Se muestran garantías con fecha de vencimiento anterior a hoy. Úsalas para seguimiento inmediato con proveedor o reemplazo.
                        @else
                            Se muestran garantías que vencen hoy y hasta dentro de <strong>{{ $resolvedWindowDays }} días</strong>.
                        @endif
                    </div>
                </div>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} alerta{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                        <thead>
                            <tr>
                                <th>Activo</th>
                                <th>Ubicación</th>
                                <th>Proveedor</th>
                                <th>Vencimiento</th>
                                <th class="text-end">{{ $type === 'expired' ? 'Días vencidos' : 'Días restantes' }}</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alerts as $asset)
                                @php
                                    $daysValue = $asset->warranty_end_date
                                        ? ($type === 'expired'
                                            ? $asset->warranty_end_date->diffInDays($today)
                                            : $today->diffInDays($asset->warranty_end_date))
                                        : null;
                                @endphp
                                <tr>
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $asset->product?->name ?? '—' }}</div>
                                            <div class="small text-body-secondary text-break">
                                                <span class="me-2">Serial: <code>{{ $asset->serial }}</code></span>
                                                <span>Asset tag: <code>{{ $asset->asset_tag ?? '—' }}</code></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $asset->location?->name ?? '—' }}</td>
                                    <td class="min-w-0">
                                        <span class="text-truncate d-inline-block" style="max-width: 14rem;">
                                            {{ $asset->warrantySupplier?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($asset->warranty_end_date)
                                            <div class="small text-body-secondary">Fecha</div>
                                            <div class="fw-semibold">{{ $asset->warranty_end_date->format('d/m/Y') }}</div>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($daysValue !== null)
                                            <x-ui.badge :tone="$modeTone" variant="compact" :with-rail="false">
                                                {{ $daysValue }} día{{ $daysValue === 1 ? '' : 's' }}
                                            </x-ui.badge>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id, 'returnTo' => $returnTo]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver detalle
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-shield-check"
                                                :title="$emptyTitle"
                                                :description="$emptyDescription"
                                                compact
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $alerts->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
