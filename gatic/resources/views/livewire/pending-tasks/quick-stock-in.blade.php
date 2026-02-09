<div class="d-inline-block">
    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="open">
        <i class="bi bi-cart-plus" aria-hidden="true"></i> Carga rápida
    </button>

    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Carga rápida</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Cerrar"></button>
                    </div>

                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="mb-3 text-muted small">
                                Captura mínima para crear una tarea pendiente sin frenar la operación.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Tipo de producto <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            id="qs-product-existing"
                                            value="existing"
                                            wire:model.live="productMode"
                                        >
                                        <label class="form-check-label" for="qs-product-existing">
                                            Producto existente
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            id="qs-product-placeholder"
                                            value="placeholder"
                                            wire:model.live="productMode"
                                        >
                                        <label class="form-check-label" for="qs-product-placeholder">
                                            Placeholder (nuevo)
                                        </label>
                                    </div>
                                </div>
                                @error('productMode')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($productMode === 'existing')
                                <div class="mb-3">
                                    <label for="qs-productId" class="form-label">
                                        Producto <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="qs-productId"
                                        class="form-select @error('productId') is-invalid @enderror"
                                        wire:model.live="productId"
                                    >
                                        <option value="">Seleccionar...</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product['id'] }}">
                                                {{ $product['name'] }}
                                                ({{ $product['is_serialized'] ? 'Serializado' : 'Por cantidad' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('productId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if (is_array($selectedProduct))
                                        <div class="form-text">
                                            Tipo detectado:
                                            <strong>{{ ($selectedProduct['is_serialized'] ?? false) ? 'Serializado' : 'Por cantidad' }}</strong>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="mb-3">
                                    <label for="qs-placeholderProductName" class="form-label">
                                        Nombre del producto <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="qs-placeholderProductName"
                                        class="form-control @error('placeholderProductName') is-invalid @enderror"
                                        wire:model="placeholderProductName"
                                        placeholder="Ej. Cable HDMI 2m"
                                    >
                                    @error('placeholderProductName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        ¿Es serializado? <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="qs-placeholder-serialized-yes"
                                                value="1"
                                                wire:model.live="placeholderIsSerialized"
                                            >
                                            <label class="form-check-label" for="qs-placeholder-serialized-yes">
                                                Sí, por serial
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="qs-placeholder-serialized-no"
                                                value="0"
                                                wire:model.live="placeholderIsSerialized"
                                            >
                                            <label class="form-check-label" for="qs-placeholder-serialized-no">
                                                No, por cantidad
                                            </label>
                                        </div>
                                    </div>
                                    @error('placeholderIsSerialized')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            @if ($resolvedIsSerialized === true)
                                <div class="mb-3">
                                    <label for="qs-serialsInput" class="form-label">
                                        Seriales (1 por línea) <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="qs-serialsInput"
                                        class="form-control @error('serialsInput') is-invalid @enderror"
                                        wire:model="serialsInput"
                                        rows="6"
                                        placeholder="Ej:\nABC123\nABC124\nABC125"
                                    ></textarea>
                                    @error('serialsInput')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Máximo {{ $maxLines }} líneas. Se ignoran líneas vacías.
                                    </div>
                                </div>
                            @elseif ($resolvedIsSerialized === false)
                                <div class="mb-3">
                                    <label for="qs-quantity" class="form-label">
                                        Cantidad <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="qs-quantity"
                                        class="form-control @error('quantity') is-invalid @enderror"
                                        wire:model="quantity"
                                        min="1"
                                    >
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            <div class="mb-0">
                                <label for="qs-note" class="form-label">Nota (opcional)</label>
                                <textarea
                                    id="qs-note"
                                    class="form-control @error('note') is-invalid @enderror"
                                    wire:model="note"
                                    rows="2"
                                    placeholder="Contexto o motivo (opcional)..."
                                ></textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="close">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Crear tarea</span>
                                <span wire:loading.inline wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

