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
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                    Cancelar
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    Ajustar activo: {{ $asset->serial }}
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <strong>Atención:</strong> Este cambio afecta el baseline del inventario y quedará registrado en auditoría.
                    </div>

                    <form wire:submit="save">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Producto</label>
                                <p class="form-control-plaintext">{{ $product->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Serial</label>
                                <p class="form-control-plaintext">{{ $asset->serial }}</p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado actual</label>
                                <p class="form-control-plaintext">{{ $currentStatus }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ubicación actual</label>
                                <p class="form-control-plaintext">{{ $asset->location?->name ?? '-' }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="newStatus" class="form-label fw-semibold">Nuevo estado <span class="text-danger">*</span></label>
                            <select
                                id="newStatus"
                                class="form-select @error('newStatus') is-invalid @enderror"
                                wire:model="newStatus"
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('newStatus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="newLocationId" class="form-label fw-semibold">Nueva ubicación <span class="text-danger">*</span></label>
                            <select
                                id="newLocationId"
                                class="form-select @error('newLocationId') is-invalid @enderror"
                                wire:model="newLocationId"
                            >
                                <option value="">Seleccione una ubicación</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                @endforeach
                            </select>
                            @error('newLocationId')
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

                        @if ($currentStatus !== $newStatus || $currentLocationId !== $newLocationId)
                            <div class="alert alert-info mb-3">
                                <strong>Vista previa del cambio:</strong>
                                <ul class="mb-0 mt-1">
                                    @if ($currentStatus !== $newStatus)
                                        <li>Estado: <strong>{{ $currentStatus }}</strong> → <strong>{{ $newStatus }}</strong></li>
                                    @endif
                                    @if ($currentLocationId !== $newLocationId)
                                        <li>Ubicación cambiará</li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-secondary" href="{{ route('inventory.products.assets.show', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
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
