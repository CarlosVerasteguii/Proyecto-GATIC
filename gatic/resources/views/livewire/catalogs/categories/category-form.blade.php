<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    {{ $isEdit ? 'Editar categoría' : 'Nueva categoría' }}
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <label for="category-name" class="form-label">Nombre</label>
                        <input
                            id="category-name"
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            wire:model="name"
                        />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-2">
                        <input
                            id="category-is-serialized"
                            type="checkbox"
                            class="form-check-input @error('is_serialized') is-invalid @enderror"
                            wire:model.live="is_serialized"
                        />
                        <label class="form-check-label" for="category-is-serialized">Serializada</label>
                        @error('is_serialized')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input
                            id="category-requires-asset-tag"
                            type="checkbox"
                            class="form-check-input @error('requires_asset_tag') is-invalid @enderror"
                            wire:model="requires_asset_tag"
                            @disabled(! $is_serialized)
                        />
                        <label class="form-check-label" for="category-requires-asset-tag">Requiere asset_tag</label>
                        <div class="form-text">
                            Solo aplica si la categoría es serializada.
                        </div>
                        @error('requires_asset_tag')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category-default-useful-life" class="form-label">Vida útil default (meses)</label>
                        <input
                            id="category-default-useful-life"
                            type="number"
                            min="1"
                            max="600"
                            class="form-control @error('default_useful_life_months') is-invalid @enderror"
                            wire:model="default_useful_life_months"
                            @disabled(! $is_serialized)
                            placeholder="Ej. 60"
                        />
                        <div class="form-text">
                            Opcional. Solo aplica para categorías serializadas. Si no aplica, se guarda como vacío.
                        </div>
                        @error('default_useful_life_months')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                            Guardar
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('catalogs.categories.index') }}">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>