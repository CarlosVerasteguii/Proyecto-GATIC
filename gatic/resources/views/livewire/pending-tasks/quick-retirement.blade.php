<div class="d-inline-block">
    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="open">
        <i class="bi bi-cart-dash" aria-hidden="true"></i> Retiro rápido
    </button>

    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Retiro rápido</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Cerrar"></button>
                    </div>

                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="mb-3 text-muted small">
                                Captura mínima para crear una tarea pendiente de retiro.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Modo <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            id="qr-mode-serials"
                                            value="serials"
                                            wire:model.live="mode"
                                        >
                                        <label class="form-check-label" for="qr-mode-serials">
                                            Por seriales
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            id="qr-mode-qty"
                                            value="product_quantity"
                                            wire:model.live="mode"
                                        >
                                        <label class="form-check-label" for="qr-mode-qty">
                                            Por producto + cantidad
                                        </label>
                                    </div>
                                </div>
                                @error('mode')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($mode === 'serials')
                                <div class="mb-3">
                                    <label for="qr-serialsInput" class="form-label">
                                        Seriales (1 por línea) <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="qr-serialsInput"
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
                            @else
                                <div class="row g-3">
                                    <div class="col-12 col-md-8">
                                        <label for="qr-productId" class="form-label">
                                            Producto <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="qr-productId"
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
                                    <div class="col-12 col-md-4">
                                        <label for="qr-quantity" class="form-label">
                                            Cantidad <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="qr-quantity"
                                            class="form-control @error('quantity') is-invalid @enderror"
                                            wire:model="quantity"
                                            min="1"
                                        >
                                        @error('quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3">
                                <label for="qr-reason" class="form-label">
                                    Motivo de retiro <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    id="qr-reason"
                                    class="form-control @error('reason') is-invalid @enderror"
                                    wire:model="reason"
                                    rows="2"
                                    maxlength="255"
                                    placeholder="Ej: Equipo dañado, obsoleto, baja autorizada..."
                                ></textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <label for="qr-note" class="form-label">Nota (opcional)</label>
                                <textarea
                                    id="qr-note"
                                    class="form-control @error('note') is-invalid @enderror"
                                    wire:model="note"
                                    rows="2"
                                    placeholder="Notas adicionales (opcional)..."
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
                            <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="save">
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
