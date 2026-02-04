<div class="container position-relative">
    <x-ui.long-request target="save" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    {{ $isEdit ? 'Editar producto' : 'Nuevo producto' }}
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <label for="product-name" class="form-label">Nombre</label>
                        <input
                            id="product-name"
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            wire:model.defer="name"
                        />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="product-category" class="form-label">Categoría</label>
                        <select
                            id="product-category"
                            class="form-select @error('category_id') is-invalid @enderror"
                            wire:model.live="category_id"
                            @disabled($isEdit)
                        >
                            <option value="">Selecciona una categoría…</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <input
                            type="text"
                            class="form-control"
                            value="{{ $categoryIsSerialized ? 'Serializado' : 'Por cantidad' }}"
                            readonly
                        />
                    </div>

                    <div class="mb-3">
                        <label for="product-brand" class="form-label">Marca (opcional)</label>
                        <select
                            id="product-brand"
                            class="form-select @error('brand_id') is-invalid @enderror"
                            wire:model.defer="brand_id"
                        >
                            <option value="">Sin marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand['id'] }}">{{ $brand['name'] }}</option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="product-supplier" class="form-label">Proveedor (opcional)</label>
                        <select
                            id="product-supplier"
                            class="form-select @error('supplier_id') is-invalid @enderror"
                            wire:model.defer="supplier_id"
                        >
                            <option value="">Sin proveedor</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if (! $categoryIsSerialized)
                        <div class="mb-3">
                            <label for="product-qty-total" class="form-label">Stock total</label>
                            <input
                                id="product-qty-total"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="form-control @error('qty_total') is-invalid @enderror"
                                wire:model.defer="qty_total"
                            />
                            @error('qty_total')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="product-low-stock-threshold" class="form-label">Umbral de stock bajo (opcional)</label>
                            <input
                                id="product-low-stock-threshold"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="form-control @error('low_stock_threshold') is-invalid @enderror"
                                wire:model.defer="low_stock_threshold"
                                placeholder="Ej: 10"
                            />
                            <div class="form-text">
                                Si el stock total cae a este valor o menos, el producto aparece en alertas de stock bajo.
                            </div>
                            @error('low_stock_threshold')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                            Guardar
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('inventory.products.index') }}">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
