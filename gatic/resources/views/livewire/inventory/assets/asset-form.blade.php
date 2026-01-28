<div class="container position-relative">
    <x-ui.long-request target="save" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    {{ $isEdit ? 'Editar activo' : 'Nuevo activo' }}
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" class="form-control" value="{{ $product?->name ?? '' }}" readonly />
                    </div>

                    @if (! $productIsSerialized)
                        <div class="alert alert-warning mb-0">
                            No hay activos para productos por cantidad.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="asset-serial" class="form-label">Serial</label>
                            <input
                                id="asset-serial"
                                type="text"
                                class="form-control @error('serial') is-invalid @enderror"
                                wire:model.defer="serial"
                            />
                            @error('serial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="asset-asset-tag" class="form-label">
                                Asset tag @if ($requiresAssetTag) <span class="text-danger">*</span> @endif
                            </label>
                            <input
                                id="asset-asset-tag"
                                type="text"
                                class="form-control @error('asset_tag') is-invalid @enderror"
                                wire:model.defer="asset_tag"
                            />
                            @error('asset_tag')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="asset-location" class="form-label">Ubicación</label>
                            <select
                                id="asset-location"
                                class="form-select @error('location_id') is-invalid @enderror"
                                wire:model.defer="location_id"
                            >
                                <option value="">Selecciona una ubicación.</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="asset-status" class="form-label">Estado</label>
                            <select
                                id="asset-status"
                                class="form-select @error('status') is-invalid @enderror @error('current_employee_id') is-invalid @enderror"
                                wire:model.defer="status"
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('current_employee_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                                Guardar
                            </button>
                            <a class="btn btn-outline-secondary" href="{{ route('inventory.products.assets.index', ['product' => $product->id]) }}">
                                Cancelar
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
