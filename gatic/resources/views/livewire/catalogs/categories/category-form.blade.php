<div class="container position-relative catalogs-form-page catalogs-categories-form-page">
    <x-ui.long-request target="save" />

    @php
        $pageTitle = $isEdit ? 'Editar categoría' : 'Nueva categoría';
        $pageSubtitle = $isEdit
            ? 'Ajusta reglas y campos opcionales para los activos de esta categoría.'
            : 'Define el nombre y las reglas base para los activos que registrarás.';

        $serializedSummary = $is_serialized ? 'Serializada' : 'No serializada';
        $assetTagSummary = $is_serialized && $requires_asset_tag ? 'Requerido' : 'No requerido';
        $lifeSummary = $is_serialized && is_string($default_useful_life_months) && trim($default_useful_life_months) !== ''
            ? trim($default_useful_life_months) . ' meses'
            : 'No aplica';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <div class="card catalogs-form-card">
                <div class="card-header catalogs-form-card__header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="min-w-0">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Catálogos', 'url' => route('catalogs.categories.index')],
                            ['label' => 'Categorías', 'url' => route('catalogs.categories.index')],
                            ['label' => $pageTitle, 'url' => null],
                        ]" />
                        <h1 class="h5 mb-1">{{ $pageTitle }}</h1>
                        <p class="text-body-secondary mb-0 catalogs-form-card__subtitle">{{ $pageSubtitle }}</p>
                    </div>

                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('catalogs.categories.index') }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver
                    </a>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            Revisa los campos marcados para continuar.
                        </div>
                    @endif

                    <form wire:submit="save">
                        <div class="row g-3">
                            <div class="col-12 col-xl-8">
                                <section class="catalogs-form-section">
                                    <h2 class="catalogs-form-section__title">
                                        <i class="bi bi-folder" aria-hidden="true"></i>
                                        Detalles
                                    </h2>

                                    <div>
                                        <label for="category-name" class="form-label">Nombre</label>
                                        <input
                                            id="category-name"
                                            name="name"
                                            type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            wire:model.blur="name"
                                            placeholder="Ejemplo: Comunicaciones…"
                                            autocomplete="off"
                                            maxlength="255"
                                            required
                                            autofocus
                                        />
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Se usa para filtrar y organizar activos y productos en reportes.
                                        </div>
                                    </div>
                                </section>

                                <section class="catalogs-form-section">
                                    <h2 class="catalogs-form-section__title">
                                        <i class="bi bi-sliders" aria-hidden="true"></i>
                                        Reglas de activos
                                    </h2>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-check form-switch catalogs-form-switch">
                                                <input
                                                    id="category-is-serialized"
                                                    name="is_serialized"
                                                    type="checkbox"
                                                    class="form-check-input @error('is_serialized') is-invalid @enderror"
                                                    wire:model.live="is_serialized"
                                                />
                                                <label class="form-check-label fw-semibold" for="category-is-serialized">
                                                    Serializada
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                Al activarlo, los activos de esta categoría se registran de forma individual (ideal para equipo con control y trazabilidad).
                                            </div>
                                            @error('is_serialized')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check form-switch catalogs-form-switch">
                                                <input
                                                    id="category-requires-asset-tag"
                                                    name="requires_asset_tag"
                                                    type="checkbox"
                                                    class="form-check-input @error('requires_asset_tag') is-invalid @enderror"
                                                    wire:model="requires_asset_tag"
                                                    @disabled(! $is_serialized)
                                                />
                                                <label class="form-check-label fw-semibold" for="category-requires-asset-tag">
                                                    Requiere asset tag
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                @if ($is_serialized)
                                                    Obliga a capturar un asset tag para identificar el activo.
                                                @else
                                                    Activa <strong>Serializada</strong> para habilitar este ajuste.
                                                @endif
                                            </div>
                                            @error('requires_asset_tag')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="category-default-useful-life" class="form-label">Vida útil default</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body">
                                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                                </span>
                                                <input
                                                    id="category-default-useful-life"
                                                    name="default_useful_life_months"
                                                    type="number"
                                                    min="1"
                                                    max="600"
                                                    class="form-control @error('default_useful_life_months') is-invalid @enderror"
                                                    wire:model="default_useful_life_months"
                                                    @disabled(! $is_serialized)
                                                    placeholder="Ej. 48"
                                                    inputmode="numeric"
                                                />
                                                <span class="input-group-text bg-body">meses</span>
                                            </div>
                                            <div class="form-text">
                                                @if ($is_serialized)
                                                    Opcional. Si lo dejas vacío, no se aplica vida útil por defecto.
                                                @else
                                                    Disponible solo para categorías serializadas.
                                                @endif
                                            </div>
                                            @error('default_useful_life_months')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                <div class="d-flex flex-wrap gap-2 catalogs-form-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                    >
                                        <span wire:loading.remove wire:target="save">
                                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                            Guardar
                                        </span>
                                        <span wire:loading.inline wire:target="save">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                Guardando…
                                            </span>
                                        </span>
                                    </button>

                                    <a class="btn btn-outline-secondary" href="{{ route('catalogs.categories.index') }}">
                                        Cancelar
                                    </a>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <aside class="catalogs-form-summary">
                                    <div class="catalogs-form-summary__title">
                                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                                        Resumen
                                    </div>

                                    <dl class="catalogs-form-summary__meta">
                                        <div>
                                            <dt>Modo</dt>
                                            <dd>{{ $serializedSummary }}</dd>
                                        </div>
                                        <div>
                                            <dt>Asset tag</dt>
                                            <dd>{{ $assetTagSummary }}</dd>
                                        </div>
                                        <div>
                                            <dt>Vida útil</dt>
                                            <dd>{{ $lifeSummary }}</dd>
                                        </div>
                                    </dl>

                                    <div class="catalogs-form-summary__hint">
                                        <strong>Tip:</strong> Si la categoría no es serializada, los campos avanzados se deshabilitan automáticamente para evitar configuraciones inconsistentes.
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
