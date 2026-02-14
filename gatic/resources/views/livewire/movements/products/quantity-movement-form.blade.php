<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Registrar movimiento</h4>
                <a href="{{ route('inventory.products.show', ['product' => $product->id]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

            {{-- Info del producto --}}
            <div class="card mb-3">
                <div class="card-header">
                    Producto
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Nombre</dt>
                        <dd class="col-sm-9">{{ $product->name }}</dd>

                        <dt class="col-sm-3">Categoria</dt>
                        <dd class="col-sm-9">{{ $product->category?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Stock actual</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-primary fs-6">{{ $currentStock }}</span>
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Formulario --}}
            <div class="card">
                <div class="card-header">
                    Datos del movimiento
                </div>
                <div class="card-body">
                    <form wire:submit="register">
                        {{-- Tipo de movimiento --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Tipo de movimiento <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input
                                        type="radio"
                                        id="direction_out"
                                        wire:model="direction"
                                        value="out"
                                        class="form-check-input @error('direction') is-invalid @enderror"
                                    >
                                    <label for="direction_out" class="form-check-label">
                                        <i class="bi bi-box-arrow-up text-danger me-1"></i> Salida
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input
                                        type="radio"
                                        id="direction_in"
                                        wire:model="direction"
                                        value="in"
                                        class="form-check-input @error('direction') is-invalid @enderror"
                                    >
                                    <label for="direction_in" class="form-check-label">
                                        <i class="bi bi-box-arrow-in-down text-success me-1"></i> Entrada / Devolucion
                                    </label>
                                </div>
                            </div>
                            @error('direction')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Cantidad --}}
                        <div class="mb-3">
                            <label for="qty" class="form-label">
                                Cantidad <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                id="qty"
                                wire:model="qty"
                                class="form-control @error('qty') is-invalid @enderror"
                                min="1"
                                placeholder="Cantidad a mover"
                            >
                            @error('qty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Empleado --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Empleado <span class="text-danger">*</span>
                            </label>
                            <livewire:ui.employee-combobox wire:model="employeeId" />
                            @error('employeeId')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nota --}}
                        <div class="mb-3">
                            <label for="note" class="form-label">
                                Nota <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="note"
                                wire:model="note"
                                class="form-control @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Motivo del movimiento (minimo 5 caracteres)"
                                maxlength="1000"
                            ></textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ mb_strlen($note) }}/1000 caracteres
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('inventory.products.show', ['product' => $product->id]) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="register">
                                    <i class="bi bi-check-circle me-1"></i> Registrar
                                </span>
                                <span wire:loading wire:target="register">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Registrando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
