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
                                class="form-select @error('status') is-invalid @enderror"
                                wire:model.live="status"
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($requiresEmployeeSelection)
                            <div class="mb-3">
                                <label class="form-label">
                                    Empleado <span class="text-danger">*</span>
                                </label>
                                <livewire:ui.employee-combobox wire:model.live="current_employee_id" />
                                @error('current_employee_id')
                                    <div class="invalid-feedback d-block mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        @endif

                        {{-- Sección Garantía --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                Garantía <span class="text-muted fw-normal">(opcional)</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="asset-warranty-start" class="form-label">Fecha inicio</label>
                                        <input
                                            id="asset-warranty-start"
                                            type="date"
                                            class="form-control @error('warrantyStartDate') is-invalid @enderror"
                                            wire:model.defer="warrantyStartDate"
                                        />
                                        @error('warrantyStartDate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="asset-warranty-end" class="form-label">Fecha fin</label>
                                        <input
                                            id="asset-warranty-end"
                                            type="date"
                                            class="form-control @error('warrantyEndDate') is-invalid @enderror"
                                            wire:model.defer="warrantyEndDate"
                                        />
                                        @error('warrantyEndDate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="asset-warranty-supplier" class="form-label">Proveedor de garantía</label>
                                    <select
                                        id="asset-warranty-supplier"
                                        class="form-select @error('warrantySupplierId') is-invalid @enderror"
                                        wire:model.defer="warrantySupplierId"
                                    >
                                        <option value="">Sin proveedor</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('warrantySupplierId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-0">
                                    <label for="asset-warranty-notes" class="form-label">Notas de garantía</label>
                                    <textarea
                                        id="asset-warranty-notes"
                                        class="form-control @error('warrantyNotes') is-invalid @enderror"
                                        wire:model.defer="warrantyNotes"
                                        rows="3"
                                        placeholder="Detalles adicionales sobre la garantía..."
                                    ></textarea>
                                    @error('warrantyNotes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
