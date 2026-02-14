<div
    class="container"
    x-data="{}"
    x-on:focus-field.window="
        const field = $event.detail.field;
        let el = null;
        if (field === 'employeeId') {
            el = document.querySelector('[role=combobox]');
        } else if (field === 'note') {
            el = document.getElementById('note');
        }
        if (el) {
            el.focus();
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    "
>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Asignar Activo</h4>
                <a href="{{ $returnTo ?: route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

            {{-- Info del activo --}}
            <div class="card mb-3">
                <div class="card-header">
                    Activo a asignar
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Producto</dt>
                        <dd class="col-sm-9">{{ $product->name }}</dd>

                        <dt class="col-sm-3">Serial</dt>
                        <dd class="col-sm-9">{{ $asset->serial }}</dd>

                        @if ($asset->asset_tag)
                            <dt class="col-sm-3">Asset tag</dt>
                            <dd class="col-sm-9">{{ $asset->asset_tag }}</dd>
                        @endif

                        <dt class="col-sm-3">Estado actual</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-success">{{ $asset->status }}</span>
                        </dd>

                        <dt class="col-sm-3">Ubicación</dt>
                        <dd class="col-sm-9">{{ $asset->location?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Formulario --}}
            <div class="card">
                <div class="card-header">
                    Datos de la asignación
                </div>
                <div class="card-body">
                    <form wire:submit="assign">
                        {{-- Empleado --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Empleado <span class="text-danger">*</span>
                            </label>
                            <livewire:ui.employee-combobox wire:model.live="employeeId" />
                            @error('employeeId')
                                <div class="invalid-feedback d-block mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Nota --}}
                        <div class="mb-3">
                            <label for="note" class="form-label">
                                Nota <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="note"
                                wire:model.blur="note"
                                class="form-control @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Motivo de la asignación (mínimo 5 caracteres)"
                                maxlength="1000"
                            ></textarea>
                            @error('note')
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                {{ strlen($note) }}/1000 caracteres (mínimo 5)
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a
                                href="{{ $returnTo ?: route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]) }}"
                                class="btn btn-outline-secondary"
                                @if($isSubmitting) aria-disabled="true" style="pointer-events: none; opacity: 0.65;" @endif
                            >
                                Cancelar
                            </a>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                @if($isSubmitting) disabled @endif
                                wire:loading.attr="disabled"
                                style="min-width: 120px;"
                            >
                                <span wire:loading.remove wire:target="assign">
                                    <i class="bi bi-person-check me-1"></i> Asignar
                                </span>
                                <span wire:loading wire:target="assign">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Asignando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
