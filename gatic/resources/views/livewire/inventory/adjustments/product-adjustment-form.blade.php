<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
                    Cancelar
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    Ajustar inventario: {{ $product->name }}
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <strong>Atención:</strong> Este cambio afecta el baseline del inventario y quedará registrado en auditoría.
                    </div>

                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Cantidad actual</label>
                            <p class="form-control-plaintext">{{ $currentQty }}</p>
                        </div>

                        <div class="mb-3">
                            <label for="newQty" class="form-label fw-semibold">Nueva cantidad <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                id="newQty"
                                class="form-control @error('newQty') is-invalid @enderror"
                                wire:model="newQty"
                                min="0"
                            >
                            @error('newQty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label fw-semibold">Motivo del ajuste <span class="text-danger">*</span></label>
                            <textarea
                                id="reason"
                                class="form-control @error('reason') is-invalid @enderror"
                                rows="3"
                                wire:model="reason"
                                placeholder="Explique por qué se realiza este ajuste..."
                            ></textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($currentQty !== $newQty)
                            <div class="alert alert-info mb-3">
                                <strong>Vista previa:</strong>
                                Cantidad cambiará de <strong>{{ $currentQty }}</strong> a <strong>{{ $newQty }}</strong>.
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-secondary" href="{{ route('inventory.products.show', ['product' => $product->id] + $returnQuery) }}">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                                Aplicar ajuste
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
