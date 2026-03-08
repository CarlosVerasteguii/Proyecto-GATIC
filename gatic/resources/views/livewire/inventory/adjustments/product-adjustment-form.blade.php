<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
        $delta = $newQty !== null && $currentQty !== null ? $newQty - $currentQty : null;
        $deltaTone = $delta === null ? 'neutral' : ($delta > 0 ? 'success' : ($delta < 0 ? 'warning' : 'neutral'));
        $deltaLabel = match (true) {
            $delta === null => 'Sin cambio calculado',
            $delta > 0 => 'Incremento',
            $delta < 0 => 'Disminución',
            default => 'Sin cambio',
        };
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <x-ui.detail-header title="Ajustar inventario" :subtitle="$product->name">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => $product->name, 'url' => route('inventory.products.show', ['product' => $product->id] + $returnQuery)],
                        ['label' => 'Ajuste', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Por cantidad</x-ui.badge>
                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">Ajuste manual</x-ui.badge>
                </x-slot:status>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Cantidad actual" :value="$currentQty ?? 0" />
                    <x-ui.detail-header-kpi label="Nueva cantidad" :value="$newQty ?? 0" :variant="$deltaTone === 'success' ? 'success' : ($deltaTone === 'warning' ? 'warning' : null)" />
                </x-slot:kpis>

                <x-slot:actions>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver al producto
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <form wire:submit="save">
                <x-ui.section-card
                    title="Contexto actual"
                    subtitle="Valida el baseline antes de aplicar el ajuste."
                    icon="bi-box-seam"
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
                                <div class="small text-body-secondary text-uppercase fw-semibold">Cantidad actual</div>
                                <div class="fw-semibold mt-2">{{ $currentQty }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Nueva cantidad</div>
                                <div class="fw-semibold mt-2">{{ $newQty ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Impacto</div>
                                <div class="mt-2">
                                    <x-ui.badge :tone="$deltaTone" variant="compact" :with-rail="false">
                                        {{ $deltaLabel }}
                                    </x-ui.badge>
                                </div>
                                <div class="small text-body-secondary mt-2">
                                    @if ($delta === null)
                                        Ingresa una nueva cantidad para calcular el cambio.
                                    @elseif ($delta === 0)
                                        El ajuste conservará la misma cantidad.
                                    @else
                                        Diferencia de {{ $delta > 0 ? '+' : '' }}{{ $delta }} unidad{{ abs($delta) === 1 ? '' : 'es' }}.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Aplicar ajuste"
                    subtitle="Este cambio afecta el baseline del inventario y quedará registrado en auditoría."
                    icon="bi-sliders"
                    class="mb-4"
                >
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle mt-1" aria-hidden="true"></i>
                        <div>
                            Usa este flujo solo para correcciones administrativas. Los movimientos operativos normales deben seguir su proceso habitual.
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-lg-5">
                            <label for="newQty" class="form-label">Nueva cantidad <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                id="newQty"
                                class="form-control @error('newQty') is-invalid @enderror"
                                wire:model.live="newQty"
                                min="0"
                                inputmode="numeric"
                            >
                            <div class="form-text">Ingresa la cantidad física correcta después de tu revisión.</div>
                            @error('newQty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-7">
                            <label for="reason" class="form-label">Motivo del ajuste <span class="text-danger">*</span></label>
                            <textarea
                                id="reason"
                                class="form-control @error('reason') is-invalid @enderror"
                                rows="4"
                                wire:model.blur="reason"
                                placeholder="Explica qué se corrigió y por qué fue necesario."
                            ></textarea>
                            <div class="form-text">Este texto quedará visible en el historial de ajustes.</div>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 mt-4 bg-body-tertiary" aria-live="polite">
                        <div class="small text-body-secondary text-uppercase fw-semibold mb-2">Vista previa del cambio</div>
                        @if ($currentQty !== $newQty)
                            <div class="fw-semibold">
                                La cantidad cambiará de <strong>{{ $currentQty }}</strong> a <strong>{{ $newQty }}</strong>.
                            </div>
                            <div class="small text-body-secondary mt-2">
                                {{ $delta > 0 ? 'Se incrementará el inventario total.' : 'Se reducirá el inventario total.' }}
                            </div>
                        @else
                            <div class="text-body-secondary">
                                No hay diferencia entre la cantidad actual y la nueva cantidad.
                            </div>
                        @endif
                    </div>
                </x-ui.section-card>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
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
