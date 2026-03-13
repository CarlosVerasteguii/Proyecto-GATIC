<div class="d-inline-block">
    <button
        type="button"
        class="btn btn-sm btn-outline-danger"
        wire:click="open"
        aria-haspopup="dialog"
        aria-expanded="{{ $showModal ? 'true' : 'false' }}"
        aria-controls="quickRetirementModal-{{ $this->getId() }}"
    >
        <i class="bi bi-cart-dash" aria-hidden="true"></i> Retiro rápido
    </button>

    @if ($showModal)
        @php
            $modalId = 'quickRetirementModal-'.$this->getId();
            $titleId = 'quickRetirementModalTitle-'.$this->getId();
            $descriptionId = 'quickRetirementModalDescription-'.$this->getId();
            $previewCount = is_array($serialPreview) ? (int) ($serialPreview['count'] ?? 0) : 0;
            $previewDuplicates = is_array($serialPreview) ? (int) ($serialPreview['duplicates'] ?? 0) : 0;
            $duplicateSample = is_array($serialPreview) ? ($serialPreview['duplicate_sample'] ?? []) : [];
        @endphp

        <div
            id="{{ $modalId }}"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $titleId }}"
            aria-describedby="{{ $descriptionId }}"
            wire:keydown.escape="close"
            style="background: rgba(0,0,0,0.5);"
        >
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="modal-title mb-0" id="{{ $titleId }}">Retiro rápido</h5>
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">Pending Tasks</x-ui.badge>
                                <x-ui.badge tone="danger" variant="compact" :with-rail="false">Retiro</x-ui.badge>
                            </div>
                            <p id="{{ $descriptionId }}" class="text-body-secondary small mb-0">
                                Captura un retiro mínimo para dejar trazabilidad inmediata y terminar de procesarlo después.
                            </p>
                        </div>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Cerrar"></button>
                    </div>

                    <form wire:submit="save" novalidate>
                        <div class="modal-body">
                            @if ($errors->any())
                                <div class="alert alert-danger mb-4" role="alert" aria-live="polite">
                                    <div class="fw-semibold mb-2">Revisa los datos capturados.</div>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <x-ui.section-card
                                title="1. Define el modo de retiro"
                                subtitle="Elige si vas a capturar seriales o un producto por cantidad."
                                icon="bi-sliders2"
                                class="mb-3"
                            >
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="border rounded-3 p-3 h-100 d-block">
                                            <span class="d-flex align-items-start gap-2">
                                                <input
                                                    class="form-check-input mt-1"
                                                    type="radio"
                                                    id="qr-mode-serials"
                                                    name="mode"
                                                    value="serials"
                                                    wire:model.live="mode"
                                                >
                                                <span>
                                                    <span class="fw-semibold d-block">Por seriales</span>
                                                    <span class="small text-body-secondary">
                                                        Usa este modo cuando identifiques activos concretos a retirar.
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="border rounded-3 p-3 h-100 d-block">
                                            <span class="d-flex align-items-start gap-2">
                                                <input
                                                    class="form-check-input mt-1"
                                                    type="radio"
                                                    id="qr-mode-qty"
                                                    name="mode"
                                                    value="product_quantity"
                                                    wire:model.live="mode"
                                                >
                                                <span>
                                                    <span class="fw-semibold d-block">Producto + cantidad</span>
                                                    <span class="small text-body-secondary">
                                                        Úsalo solo para retiros por cantidad de productos no serializados.
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                @error('mode')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </x-ui.section-card>

                            <x-ui.section-card
                                title="2. Captura los ítems"
                                subtitle="El contenido cambia según el modo seleccionado."
                                icon="bi-clipboard2-data"
                                class="mb-3"
                            >
                                @if ($mode === 'serials')
                                    <div class="mb-3">
                                        <label for="qr-serialsInput" class="form-label">
                                            Seriales (1 por línea) <span class="text-danger">*</span>
                                        </label>
                                        <textarea
                                            id="qr-serialsInput"
                                            name="serials_input"
                                            class="form-control @error('serialsInput') is-invalid @enderror"
                                            wire:model.blur="serialsInput"
                                            rows="7"
                                            autocomplete="off"
                                            placeholder="Ej.&#10;RET001&#10;RET002&#10;RET003"
                                        ></textarea>
                                        @error('serialsInput')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Máximo {{ $maxLines }} líneas. Se ignoran líneas vacías.
                                        </div>
                                    </div>

                                    <div class="border rounded-3 p-3 bg-body-tertiary" aria-live="polite">
                                        <div class="small text-body-secondary text-uppercase fw-semibold mb-2">
                                            Resumen antes de crear
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <x-ui.badge tone="info" variant="compact" :with-rail="false">
                                                Seriales detectados <strong>{{ $previewCount }}</strong>
                                            </x-ui.badge>
                                            @if ($previewDuplicates > 0)
                                                <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                                                    Duplicados <strong>{{ $previewDuplicates }}</strong>
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                        <div class="small text-body-secondary mt-2">
                                            @if ($previewCount === 0)
                                                Pega los seriales a retirar para revisar el volumen antes de guardar.
                                            @elseif ($previewDuplicates > 0)
                                                Corrige seriales repetidos antes de crear la tarea.
                                            @else
                                                La tarea se creará con {{ $previewCount }} serial{{ $previewCount === 1 ? '' : 'es' }} pendientes de retiro.
                                            @endif
                                        </div>
                                        @if ($previewDuplicates > 0 && count($duplicateSample) > 0)
                                            <div class="small mt-2">
                                                <strong>Muestra:</strong> {{ implode(', ', $duplicateSample) }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="row g-3">
                                        <div class="col-12 col-lg-8">
                                            <label for="qr-productId" class="form-label">
                                                Producto <span class="text-danger">*</span>
                                            </label>
                                            <livewire:ui.product-combobox
                                                wire:model.live="productId"
                                                inputId="qr-productId"
                                                :key="'qr-product-combobox-' . $this->getId()"
                                            />
                                            @error('productId')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-lg-4">
                                            <label for="qr-quantity" class="form-label">
                                                Cantidad <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="number"
                                                id="qr-quantity"
                                                name="quantity"
                                                class="form-control @error('quantity') is-invalid @enderror"
                                                wire:model.blur="quantity"
                                                min="1"
                                                inputmode="numeric"
                                                autocomplete="off"
                                            >
                                            @error('quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    @if (is_array($selectedProduct))
                                        <div class="border rounded-3 p-3 bg-body-tertiary mt-3" aria-live="polite">
                                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                                <div class="fw-semibold">{{ $selectedProduct['name'] }}</div>
                                                <x-ui.badge
                                                    :tone="($selectedProduct['is_serialized'] ?? false) ? 'warning' : 'neutral'"
                                                    variant="compact"
                                                    :with-rail="false"
                                                >
                                                    {{ ($selectedProduct['is_serialized'] ?? false) ? 'Serializado' : 'Por cantidad' }}
                                                </x-ui.badge>
                                            </div>
                                            <div class="small text-body-secondary mt-2">
                                                {{ ($selectedProduct['is_serialized'] ?? false)
                                                    ? 'Este producto no puede retirarse en modo “Producto + cantidad”.'
                                                    : 'El retiro se capturará por cantidad y quedará pendiente para procesarse.' }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($quantityModeSerializedConflict)
                                        <div class="alert alert-warning mt-3 mb-0" role="alert" aria-live="polite">
                                            El producto seleccionado es serializado. Cambia al modo “Por seriales” para continuar.
                                        </div>
                                    @endif
                                @endif
                            </x-ui.section-card>

                            <x-ui.section-card
                                title="3. Contexto del retiro"
                                subtitle="Este detalle acompaña la tarea y facilita la revisión posterior."
                                icon="bi-journal-text"
                            >
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="qr-reason" class="form-label">
                                            Motivo de retiro <span class="text-danger">*</span>
                                        </label>
                                        <textarea
                                            id="qr-reason"
                                            name="reason"
                                            class="form-control @error('reason') is-invalid @enderror"
                                            wire:model.blur="reason"
                                            rows="3"
                                            maxlength="255"
                                            autocomplete="off"
                                            placeholder="Ej. Equipo dañado, baja autorizada u obsolescencia…"
                                        ></textarea>
                                        <div class="form-text">
                                            El motivo debe dejar claro por qué este retiro necesita seguimiento.
                                        </div>
                                        @error('reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="qr-note" class="form-label">Nota adicional</label>
                                        <textarea
                                            id="qr-note"
                                            name="note"
                                            class="form-control @error('note') is-invalid @enderror"
                                            wire:model.blur="note"
                                            rows="3"
                                            maxlength="5000"
                                            autocomplete="off"
                                            placeholder="Ej. Se retirará en la siguiente ventana de recolección…"
                                        ></textarea>
                                        @error('note')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </x-ui.section-card>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="close" wire:loading.attr="disabled" wire:target="save">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Crear tarea</span>
                                <span wire:loading.inline wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Guardando…
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
