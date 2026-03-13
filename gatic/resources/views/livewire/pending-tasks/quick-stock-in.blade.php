<div class="d-inline-block">
    <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        wire:click="open"
        aria-haspopup="dialog"
        aria-expanded="{{ $showModal ? 'true' : 'false' }}"
        aria-controls="quickStockInModal-{{ $this->getId() }}"
    >
        <i class="bi bi-cart-plus" aria-hidden="true"></i> Carga rápida
    </button>

    @if ($showModal)
        @php
            $modalId = 'quickStockInModal-'.$this->getId();
            $titleId = 'quickStockInModalTitle-'.$this->getId();
            $descriptionId = 'quickStockInModalDescription-'.$this->getId();
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
                                <h5 class="modal-title mb-0" id="{{ $titleId }}">Carga rápida</h5>
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">Pending Tasks</x-ui.badge>
                                <x-ui.badge tone="success" variant="compact" :with-rail="false">Entrada</x-ui.badge>
                            </div>
                            <p id="{{ $descriptionId }}" class="text-body-secondary small mb-0">
                                Captura el mínimo necesario para crear una tarea pendiente sin salir del flujo operativo.
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
                                title="1. Selecciona el producto"
                                subtitle="Define si trabajarás con un producto existente o con un placeholder operativo."
                                icon="bi-box-seam"
                                class="mb-3"
                            >
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="border rounded-3 p-3 h-100 d-block">
                                            <span class="d-flex align-items-start gap-2">
                                                <input
                                                    class="form-check-input mt-1"
                                                    type="radio"
                                                    id="qs-product-existing"
                                                    name="product_mode"
                                                    value="existing"
                                                    wire:model.live="productMode"
                                                >
                                                <span>
                                                    <span class="fw-semibold d-block">Producto existente</span>
                                                    <span class="small text-body-secondary">
                                                        Busca un producto ya registrado y reutiliza su configuración.
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
                                                    id="qs-product-placeholder"
                                                    name="product_mode"
                                                    value="placeholder"
                                                    wire:model.live="productMode"
                                                >
                                                <span>
                                                    <span class="fw-semibold d-block">Producto provisional</span>
                                                    <span class="small text-body-secondary">
                                                        Registra el intake aunque el catálogo formal se capture después.
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                @error('productMode')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                                @if ($productMode === 'existing')
                                    <div class="mt-4">
                                        <label for="qs-productId" class="form-label">
                                            Producto <span class="text-danger">*</span>
                                        </label>
                                        <livewire:ui.product-combobox
                                            wire:model.live="productId"
                                            inputId="qs-productId"
                                            :key="'qs-product-combobox-' . $this->getId()"
                                        />
                                        @error('productId')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror

                                        @if (is_array($selectedProduct))
                                            <div class="border rounded-3 p-3 bg-body-tertiary mt-3">
                                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                                    <div class="fw-semibold">{{ $selectedProduct['name'] }}</div>
                                                    <x-ui.badge
                                                        :tone="($selectedProduct['is_serialized'] ?? false) ? 'info' : 'neutral'"
                                                        variant="compact"
                                                        :with-rail="false"
                                                    >
                                                        {{ ($selectedProduct['is_serialized'] ?? false) ? 'Serializado' : 'Por cantidad' }}
                                                    </x-ui.badge>
                                                </div>
                                                <div class="small text-body-secondary mt-2">
                                                    El modo de captura se adapta al tipo del producto seleccionado.
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="row g-3 mt-1">
                                        <div class="col-12">
                                            <label for="qs-placeholderProductName" class="form-label">
                                                Nombre del producto <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="qs-placeholderProductName"
                                                name="placeholder_product_name"
                                                class="form-control @error('placeholderProductName') is-invalid @enderror"
                                                wire:model.blur="placeholderProductName"
                                                autocomplete="off"
                                                placeholder="Ej. Cable HDMI 2 m…"
                                            >
                                            @error('placeholderProductName')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">
                                                ¿Es serializado? <span class="text-danger">*</span>
                                            </label>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <label class="border rounded-3 p-3 h-100 d-block">
                                                        <span class="d-flex align-items-start gap-2">
                                                            <input
                                                                class="form-check-input mt-1"
                                                                type="radio"
                                                                id="qs-placeholder-serialized-yes"
                                                                name="placeholder_is_serialized"
                                                                value="1"
                                                                wire:model.live="placeholderIsSerialized"
                                                            >
                                                            <span>
                                                                <span class="fw-semibold d-block">Sí, por serial</span>
                                                                <span class="small text-body-secondary">
                                                                    Crearás la tarea con seriales individuales.
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
                                                                id="qs-placeholder-serialized-no"
                                                                name="placeholder_is_serialized"
                                                                value="0"
                                                                wire:model.live="placeholderIsSerialized"
                                                            >
                                                            <span>
                                                                <span class="fw-semibold d-block">No, por cantidad</span>
                                                                <span class="small text-body-secondary">
                                                                    Solo registrarás la cantidad total a ingresar.
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            @error('placeholderIsSerialized')
                                                <div class="text-danger small mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </x-ui.section-card>

                            <x-ui.section-card
                                title="2. Define la captura"
                                subtitle="Registra seriales o cantidad según el tipo de producto."
                                icon="bi-clipboard-data"
                                class="mb-3"
                            >
                                @if ($resolvedIsSerialized === true)
                                    <div class="mb-3">
                                        <label for="qs-serialsInput" class="form-label">
                                            Seriales (1 por línea) <span class="text-danger">*</span>
                                        </label>
                                        <textarea
                                            id="qs-serialsInput"
                                            name="serials_input"
                                            class="form-control @error('serialsInput') is-invalid @enderror"
                                            wire:model.blur="serialsInput"
                                            rows="7"
                                            autocomplete="off"
                                            placeholder="Ej.&#10;ABC123&#10;ABC124&#10;ABC125"
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
                                                Pega uno o más seriales para validar el volumen antes de guardar.
                                            @elseif ($previewDuplicates > 0)
                                                Revisa seriales repetidos antes de crear la tarea.
                                            @else
                                                La tarea se creará con {{ $previewCount }} serial{{ $previewCount === 1 ? '' : 'es' }} listos para procesarse.
                                            @endif
                                        </div>
                                        @if ($previewDuplicates > 0 && count($duplicateSample) > 0)
                                            <div class="small mt-2">
                                                <strong>Muestra:</strong> {{ implode(', ', $duplicateSample) }}
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($resolvedIsSerialized === false)
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="qs-quantity" class="form-label">
                                                Cantidad <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="number"
                                                id="qs-quantity"
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
                                        <div class="col-12 col-md-6">
                                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary" aria-live="polite">
                                                <div class="small text-body-secondary text-uppercase fw-semibold mb-2">
                                                    Resumen antes de crear
                                                </div>
                                                <div class="fw-semibold">
                                                    Cantidad capturada: {{ is_numeric($quantity) ? (int) $quantity : 0 }}
                                                </div>
                                                <div class="small text-body-secondary mt-2">
                                                    La tarea quedará en borrador para que la revises antes de procesarla.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <x-ui.empty-state
                                        icon="bi-arrow-up-right-circle"
                                        title="Define el producto primero"
                                        description="El siguiente paso se habilita cuando indicas si trabajarás con un producto existente o provisional."
                                        compact
                                    />
                                @endif
                            </x-ui.section-card>

                            <x-ui.section-card
                                title="3. Contexto"
                                subtitle="La nota acompaña la tarea para que el equipo entienda el intake antes de procesarlo."
                                icon="bi-chat-left-text"
                            >
                                <label for="qs-note" class="form-label">Nota operativa</label>
                                <textarea
                                    id="qs-note"
                                    name="note"
                                    class="form-control @error('note') is-invalid @enderror"
                                    wire:model.blur="note"
                                    rows="3"
                                    maxlength="5000"
                                    autocomplete="off"
                                    placeholder="Ej. Ingreso recibido de proveedor y pendiente de validación física…"
                                ></textarea>
                                <div class="form-text">
                                    Después de crear la tarea podrás abrirla desde el toast y continuar el flujo.
                                </div>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </x-ui.section-card>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="close" wire:loading.attr="disabled" wire:target="save">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
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
