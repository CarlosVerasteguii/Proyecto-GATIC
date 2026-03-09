<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
        $statusChanged = $currentStatus !== $newStatus;
        $locationChanged = $currentLocationId !== $newLocationId;
        $selectedLocation = collect($locations)->firstWhere('id', (int) $newLocationId);
        $selectedLocationName = is_array($selectedLocation) ? (string) ($selectedLocation['name'] ?? '') : 'Sin ubicación';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <x-ui.detail-header title="Ajustar activo" :subtitle="$asset->serial">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => $product->name, 'url' => route('inventory.products.show', ['product' => $product->id] + $returnQuery)],
                        ['label' => $asset->serial, 'url' => route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery)],
                        ['label' => 'Ajuste', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.status-badge :status="$currentStatus" />
                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">Ajuste manual</x-ui.badge>
                </x-slot:status>

                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver al activo
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <form wire:submit="save">
                <x-ui.section-card
                    title="Contexto actual"
                    subtitle="Valida el estado y la ubicación antes de aplicar la corrección."
                    icon="bi-hdd-stack"
                    class="mb-4"
                >
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Producto</div>
                                <div class="fw-semibold mt-2">{{ $product->name }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Serial</div>
                                <div class="fw-semibold mt-2">{{ $asset->serial }}</div>
                                <div class="small text-body-secondary mt-2">Asset tag: {{ $asset->asset_tag ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Estado actual</div>
                                <div class="mt-2">
                                    <x-ui.status-badge :status="$currentStatus" />
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Ubicación actual</div>
                                <div class="fw-semibold mt-2">{{ $asset->location?->name ?? 'Sin ubicación' }}</div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Aplicar ajuste"
                    subtitle="Este cambio impacta estado y ubicación del activo, y quedará registrado en auditoría."
                    icon="bi-sliders"
                    class="mb-4"
                >
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle mt-1" aria-hidden="true"></i>
                        <div>
                            Usa este ajuste para correcciones administrativas. Si el activo cambió por operación normal, registra el movimiento en su flujo correspondiente.
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <label for="newStatus" class="form-label">Nuevo estado <span class="text-danger">*</span></label>
                            <select
                                id="newStatus"
                                class="form-select @error('newStatus') is-invalid @enderror"
                                wire:model.live="newStatus"
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('newStatus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="newLocationId" class="form-label">Nueva ubicación <span class="text-danger">*</span></label>
                            <select
                                id="newLocationId"
                                class="form-select @error('newLocationId') is-invalid @enderror"
                                wire:model.live="newLocationId"
                            >
                                <option value="">Seleccione una ubicación</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                @endforeach
                            </select>
                            @error('newLocationId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="reason" class="form-label">Motivo del ajuste <span class="text-danger">*</span></label>
                            <textarea
                                id="reason"
                                class="form-control @error('reason') is-invalid @enderror"
                                rows="4"
                                wire:model.blur="reason"
                                placeholder="Describe qué se corrige y qué evidencia soporta el ajuste."
                            ></textarea>
                            <div class="form-text">El motivo se conservará en el historial para trazabilidad administrativa.</div>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 mt-4 bg-body-tertiary" aria-live="polite">
                        <div class="small text-body-secondary text-uppercase fw-semibold mb-2">Vista previa del cambio</div>

                        @if ($statusChanged || $locationChanged)
                            <div class="d-flex flex-column gap-2">
                                @if ($statusChanged)
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="small text-body-secondary">Estado:</span>
                                        <x-ui.status-badge :status="$currentStatus" />
                                        <i class="bi bi-arrow-right text-body-secondary" aria-hidden="true"></i>
                                        <x-ui.status-badge :status="$newStatus" />
                                    </div>
                                @endif

                                @if ($locationChanged)
                                    <div class="small text-body-secondary">
                                        Ubicación: <strong>{{ $asset->location?->name ?? 'Sin ubicación' }}</strong>
                                        a <strong>{{ $selectedLocationName }}</strong>.
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-body-secondary">
                                No hay cambios respecto al estado o la ubicación actual del activo.
                            </div>
                        @endif
                    </div>
                </x-ui.section-card>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>
                            Aplicar ajuste
                        </span>
                        <span wire:loading.inline wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            Aplicando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
