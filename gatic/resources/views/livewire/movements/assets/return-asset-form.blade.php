<div class="container">
    <x-ui.toast-container />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Devolver Activo</h4>
                <a href="{{ $returnTo ?: route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    Activo a devolver
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
                            <span class="badge bg-info text-dark">{{ $asset->status }}</span>
                        </dd>

                        <dt class="col-sm-3">Ubicación</dt>
                        <dd class="col-sm-9">{{ $asset->location?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Datos de la devolución
                </div>
                <div class="card-body">
                    <form wire:submit="returnAsset">
                        <div class="mb-3">
                            <label class="form-label">
                                Empleado
                                @if (! $employeeLocked)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            @if ($employeeLocked && $asset->currentEmployee)
                                <div class="form-control-plaintext">
                                    <a href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}" class="text-decoration-none">
                                        <strong>{{ $asset->currentEmployee->rpe }}</strong> - {{ $asset->currentEmployee->name }}
                                    </a>
                                </div>
                            @else
                                <div class="text-muted small mb-2">
                                    Sin tenencia registrada (estado legacy o ajuste manual). Selecciona un empleado para preservar trazabilidad.
                                </div>
                                <livewire:ui.employee-combobox wire:model="employeeId" />
                                @error('employeeId')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">
                                Nota <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="note"
                                wire:model="note"
                                class="form-control @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Motivo de la devolución (mínimo 5 caracteres)"
                                maxlength="1000"
                            ></textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ mb_strlen($note) }}/1000 caracteres
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $returnTo ?: route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="returnAsset">
                                    <i class="bi bi-arrow-return-left me-1"></i> Devolver
                                </span>
                                <span wire:loading wire:target="returnAsset">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Devolviendo...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
