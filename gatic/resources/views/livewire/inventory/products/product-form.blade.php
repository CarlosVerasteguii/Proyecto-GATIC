<div class="container position-relative">
    <x-ui.long-request target="save" />
    @php
        $cancelUrl = is_string($returnTo) && $returnTo !== ''
            ? $returnTo
            : route('inventory.products.index');
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    {{ $isEdit ? 'Editar producto' : 'Nuevo producto' }}
                </div>

                <div class="card-body">
                    @if (is_string($createdCategoryFeedback) && $createdCategoryFeedback !== '')
                        <div class="alert alert-{{ $createdCategoryFeedbackIsWarning ? 'warning' : 'success' }} small" role="status">
                            {{ $createdCategoryFeedback }}
                        </div>
                    @endif

                    @if (! $isEdit && is_string($returnTo) && $returnTo !== '')
                        <div class="alert alert-info small">
                            Al guardar volverás al flujo anterior y el producto nuevo quedará preseleccionado.
                        </div>
                    @endif

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

                        @if (! $isEdit)
                            @can('catalogs.manage')
                                @php
                                    $categoryReturnTo = \App\Support\Ui\ReturnToPath::browserCurrent(['created_id']);
                                    $createCategoryUrl = is_string($categoryReturnTo) && $categoryReturnTo !== ''
                                        ? route('catalogs.categories.create', ['returnTo' => $categoryReturnTo])
                                        : route('catalogs.categories.create');
                                @endphp

                                <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $createCategoryUrl }}">
                                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Crear categoría
                                    </a>
                                    <span class="text-body-secondary small">
                                        Si no existe, créala y al volver quedará seleccionada.
                                    </span>
                                </div>
                            @endcan
                        @endif
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
                        <livewire:ui.brand-combobox wire:model="brand_id" inputId="product-brand" />
                        @error('brand_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="product-supplier" class="form-label">Proveedor (opcional)</label>
                        <livewire:ui.supplier-combobox wire:model="supplier_id" inputId="product-supplier" />
                        @error('supplier_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
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
                        <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
