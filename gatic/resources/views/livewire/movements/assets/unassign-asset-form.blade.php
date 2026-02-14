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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Desasignar Activo</h4>
                <a href="{{ $returnTo ?: route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    Activo a desasignar
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
                    Datos de la desasignación
                </div>
                <div class="card-body">
                    <form wire:submit="unassignAsset">
                        <div class="mb-3">
                            <label class="form-label">
                                Empleado
                                @if (! $employeeLocked)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            @if ($employeeLocked && $asset->currentEmployee)
                                {{-- Pill visual del empleado actual --}}
                                <div class="d-flex align-items-center gap-2 p-2 bg-light border rounded">
                                    <div class="flex-grow-1">
                                        <a href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}" class="text-decoration-none fw-medium">
                                            {{ $asset->currentEmployee->rpe }}
                                        </a>
                                        <span class="text-muted mx-1">-</span>
                                        <span>{{ $asset->currentEmployee->name }}</span>
                                        @if($asset->currentEmployee->department)
                                            <span class="text-muted small ms-2">({{ $asset->currentEmployee->department }})</span>
                                        @endif
                                    </div>
                                    <span class="badge bg-info text-dark">Tenencia actual</span>
                                </div>
                            @else
                                <div class="alert alert-warning py-2 mb-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Sin tenencia registrada. Selecciona un empleado para preservar trazabilidad.
                                </div>
                                <livewire:ui.employee-combobox wire:model.live="employeeId" />
                                @error('employeeId')
                                    <div class="invalid-feedback d-block mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">
                                Nota <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="note"
                                wire:model.blur="note"
                                class="form-control @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Motivo de la desasignación (mínimo 5 caracteres)"
                                maxlength="1000"
                            ></textarea>
                            @error('note')
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                {{ mb_strlen($note) }}/1000 caracteres (mínimo 5)
                            </div>
                        </div>

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
                                style="min-width: 140px;"
                            >
                                <span wire:loading.remove wire:target="unassignAsset">
                                    <i class="bi bi-person-x me-1"></i> Desasignar
                                </span>
                                <span wire:loading wire:target="unassignAsset">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Desasignando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
