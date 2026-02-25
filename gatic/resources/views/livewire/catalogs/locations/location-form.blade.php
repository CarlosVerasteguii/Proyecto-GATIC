<div class="container position-relative catalogs-form-page catalogs-locations-form-page">
    <x-ui.long-request target="save" />

    @php
        $pageTitle = $isEdit ? 'Editar ubicación' : 'Nueva ubicación';
        $pageSubtitle = $isEdit
            ? 'Actualiza el nombre de la ubicación seleccionada.'
            : 'Agrega ubicaciones físicas para activos y productos.';

        $backUrl = is_string($returnTo) && $returnTo !== ''
            ? $returnTo
            : route('catalogs.locations.index');

        $nameSummary = is_string($name) && trim($name) !== '' ? trim($name) : 'Sin definir';
        $idSummary = $isEdit ? (string) $this->locationId : 'Se asigna al guardar';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <div class="card catalogs-form-card">
                <div class="card-header catalogs-form-card__header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="min-w-0">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Catálogos', 'url' => route('catalogs.locations.index')],
                            ['label' => 'Ubicaciones', 'url' => route('catalogs.locations.index')],
                            ['label' => $pageTitle, 'url' => null],
                        ]" />
                        <h1 class="h5 mb-1">{{ $pageTitle }}</h1>
                        <p class="text-body-secondary mb-0 catalogs-form-card__subtitle">{{ $pageSubtitle }}</p>
                    </div>

                    <a class="btn btn-sm btn-outline-secondary" href="{{ $backUrl }}">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver
                    </a>
                </div>

                <div class="card-body">
                    @if (! $isEdit && is_string($returnTo) && $returnTo !== '')
                        <div class="alert alert-info small" role="status">
                            Al guardar volverás al formulario anterior y la ubicación quedará seleccionada.
                        </div>
                    @endif

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
                                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                        Detalles
                                    </h2>

                                    <div>
                                        <label for="location-name" class="form-label">Nombre</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-body">
                                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                            </span>
                                            <input
                                                id="location-name"
                                                name="name"
                                                type="text"
                                                class="form-control @error('name') is-invalid @enderror"
                                                wire:model.blur="name"
                                                placeholder="Ejemplo: Bodega Central…"
                                                autocomplete="off"
                                                maxlength="255"
                                                required
                                                autofocus
                                            />
                                        </div>
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Se usa para ubicar activos y productos dentro del inventario.
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

                                    <a class="btn btn-outline-secondary" href="{{ $backUrl }}">
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
                                            <dt>Nombre</dt>
                                            <dd>{{ $nameSummary }}</dd>
                                        </div>
                                        <div>
                                            <dt>ID</dt>
                                            <dd>{{ $idSummary }}</dd>
                                        </div>
                                    </dl>

                                    <div class="catalogs-form-summary__hint">
                                        <strong>Tip:</strong> Usa nombres claros (por ejemplo “Almacén TI - Piso 2”) para evitar confusiones al registrar movimientos.
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

