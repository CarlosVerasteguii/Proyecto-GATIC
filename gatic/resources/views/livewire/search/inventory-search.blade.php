<div class="container position-relative search-page">
    <x-ui.long-request target="submitSearch,clearSearch,retrySearch" />

    @php
        $returnToParams = array_filter([
            'q' => $this->search,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $returnToUrl = route('inventory.search', $returnToParams);
        $returnToPath = parse_url($returnToUrl, PHP_URL_PATH) ?: '/inventory/search';
        $returnToQuery = parse_url($returnToUrl, PHP_URL_QUERY);
        $returnTo = is_string($returnToQuery) && $returnToQuery !== ''
            ? "{$returnToPath}?{$returnToQuery}"
            : $returnToPath;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Búsqueda"
                subtitle="Encuentra productos y activos desde un solo punto, con el mismo contexto visual del shell y alerts."
                searchColClass="col-12 col-lg-8"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Búsqueda', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    @if ($hasSearch && ! $this->showMinCharsMessage && ! $this->errorId)
                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            Resultados <strong>{{ number_format($totalResults) }}</strong>
                        </x-ui.badge>
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Activos <strong>{{ number_format($assetsCount) }}</strong>
                        </x-ui.badge>
                        <x-ui.badge tone="success" variant="compact" :with-rail="false">
                            Productos <strong>{{ number_format($productsCount) }}</strong>
                        </x-ui.badge>
                    @endif

                    <a href="{{ route('inventory.products.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i>
                        Productos
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <form wire:submit.prevent="submitSearch" class="search-query-panel">
                        <label for="inventory-search" class="form-label">Buscar por nombre de producto, serial o asset tag</label>

                        <div class="input-group search-query-panel__group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                id="inventory-search"
                                type="search"
                                name="q"
                                class="form-control"
                                placeholder="Ej: Laptop Dell, SN12345 o GATIC-001…"
                                wire:model.defer="search"
                                wire:loading.attr="disabled"
                                wire:target="submitSearch,retrySearch"
                                aria-describedby="inventory-search-help"
                                autocomplete="off"
                                spellcheck="false"
                                autofocus
                            />
                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="submitSearch,retrySearch"
                            >
                                <span wire:loading.remove wire:target="submitSearch,retrySearch">
                                    <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
                                </span>
                                <span wire:loading.inline wire:target="submitSearch,retrySearch">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Buscando…
                                </span>
                            </button>
                        </div>

                        <div id="inventory-search-help" class="form-text">
                            Usa el nombre del producto para explorar inventario. Si el serial o asset tag coincide exactamente, irás directo al detalle del activo.
                        </div>
                    </form>
                </x-slot:search>

                <x-slot:clearFilters>
                    @if ($hasSearch)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearSearch"
                            wire:loading.attr="disabled"
                            wire:target="submitSearch,clearSearch,retrySearch"
                            aria-label="Limpiar búsqueda"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                @if ($this->errorId)
                    <div class="mb-4">
                        <x-ui.error-alert-with-id :error-id="$this->errorId" />

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm"
                                wire:click="retrySearch"
                                wire:loading.attr="disabled"
                                wire:target="submitSearch,clearSearch,retrySearch"
                            >
                                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
                                Reintentar búsqueda
                            </button>
                        </div>
                    </div>
                @elseif ($this->showMinCharsMessage)
                    <div class="alert alert-info mb-4" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
                            <div>
                                <div class="fw-semibold">Ingresa al menos {{ $minChars }} caracteres para buscar.</div>
                                <div class="small text-body-secondary mt-1">
                                    Esto ayuda a mantener resultados útiles y evita consultas demasiado amplias.
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif (! $hasSearch)
                    <x-ui.empty-state
                        icon="bi-search"
                        title="Busca productos y activos"
                        description="Consulta inventario por nombre, serial o asset tag. La coincidencia exacta por identificador te lleva directo al detalle del activo."
                    >
                        <div class="search-empty-examples" aria-label="Ejemplos de búsqueda">
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Laptop Dell</x-ui.badge>
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">SN-DEMO-001</x-ui.badge>
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">AT-001</x-ui.badge>
                        </div>
                    </x-ui.empty-state>
                @else
                    <div class="search-overview-grid mb-4" role="status" aria-live="polite">
                        <article class="search-overview-card search-overview-card--total">
                            <div class="search-overview-card__label">Resultados</div>
                            <div class="search-overview-card__value">{{ number_format($totalResults) }}</div>
                            <p class="search-overview-card__hint mb-0">Consulta actual: <strong>{{ $this->search }}</strong></p>
                        </article>

                        <article class="search-overview-card search-overview-card--assets">
                            <div class="search-overview-card__label">Activos</div>
                            <div class="search-overview-card__value">{{ number_format($assetsCount) }}</div>
                            <p class="search-overview-card__hint mb-0">Serial, asset tag, estado, ubicación y responsable cuando aplique.</p>
                        </article>

                        <article class="search-overview-card search-overview-card--products">
                            <div class="search-overview-card__label">Productos</div>
                            <div class="search-overview-card__value">{{ number_format($productsCount) }}</div>
                            <p class="search-overview-card__hint mb-0">Disponibilidad resumida para responder rápido si hay existencia.</p>
                        </article>
                    </div>

                    @if ($this->showNoResultsMessage)
                        <x-ui.empty-state
                            variant="filter"
                            icon="bi-search"
                            title="No se encontraron resultados"
                            description="No se encontraron coincidencias para {{ $this->search }}. Prueba con otro nombre, un prefijo de serial o un asset tag exacto."
                        >
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearSearch">
                                    Limpiar búsqueda
                                </button>
                            </div>
                        </x-ui.empty-state>
                    @else
                        <div class="search-query-note mb-4">
                            <div class="search-query-note__title">Cómo leer estos resultados</div>
                            <p class="mb-0 text-body-secondary small">
                                Los activos muestran el responsable actual cuando existe. Los productos resumen disponibilidad para responder si hay inventario operativo sin abrir otra pantalla.
                            </p>
                        </div>

                        @if ($this->assets->isNotEmpty())
                            <section class="search-section mb-4" aria-labelledby="search-assets-heading">
                                <div class="search-section__header">
                                    <div>
                                        <h2 id="search-assets-heading" class="search-section__title mb-1">Activos encontrados</h2>
                                        <p class="search-section__subtitle mb-0">Coincidencias por serial o asset tag, con contexto operativo inmediato.</p>
                                    </div>

                                    <x-ui.badge tone="info" variant="compact" :with-rail="false">
                                        {{ number_format($assetsCount) }} activo{{ $assetsCount === 1 ? '' : 's' }}
                                    </x-ui.badge>
                                </div>

                                <div class="table-responsive-xl border-top">
                                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                        <thead>
                                            <tr>
                                                <th>Activo</th>
                                                <th>Estado</th>
                                                <th>Responsable</th>
                                                <th>Ubicación</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->assets as $asset)
                                                <tr wire:key="inventory-search-asset-{{ $asset->id }}">
                                                    <td class="min-w-0">
                                                        <div class="min-w-0">
                                                            <div class="fw-semibold text-truncate">{{ $asset->product?->name ?? '—' }}</div>
                                                            <div class="small text-body-secondary text-break">
                                                                <span class="me-2">Serial: <code class="search-code">{{ $asset->serial }}</code></span>
                                                                <span>Asset tag: <code class="search-code">{{ $asset->asset_tag ?? '—' }}</code></span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <x-ui.status-badge :status="$asset->status" />
                                                    </td>
                                                    <td class="min-w-0">
                                                        @if ($asset->currentEmployee)
                                                            <a
                                                                href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}"
                                                                class="text-decoration-none d-inline-flex flex-column min-w-0"
                                                            >
                                                                <span class="fw-semibold text-truncate">{{ $asset->currentEmployee->rpe }}</span>
                                                                <span class="small text-body-secondary text-truncate">{{ $asset->currentEmployee->name }}</span>
                                                            </a>
                                                        @else
                                                            <span class="text-body-secondary">Sin responsable</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-nowrap">{{ $asset->location?->name ?? '—' }}</td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <x-ui.quick-action-dropdown
                                                                :asset="$asset"
                                                                :productId="$asset->product_id"
                                                                :returnTo="$returnTo"
                                                            />

                                                            <a
                                                                class="btn btn-sm btn-outline-primary search-action-btn"
                                                                href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id, 'returnTo' => $returnTo]) }}"
                                                            >
                                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                                Ver detalle
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif

                        @if ($this->products->isNotEmpty())
                            <section class="search-section" aria-labelledby="search-products-heading">
                                <div class="search-section__header">
                                    <div>
                                        <h2 id="search-products-heading" class="search-section__title mb-1">Productos encontrados</h2>
                                        <p class="search-section__subtitle mb-0">Coincidencias por nombre con disponibilidad resumida.</p>
                                    </div>

                                    <x-ui.badge tone="success" variant="compact" :with-rail="false">
                                        {{ number_format($productsCount) }} producto{{ $productsCount === 1 ? '' : 's' }}
                                    </x-ui.badge>
                                </div>

                                <div class="table-responsive-xl border-top">
                                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th>Marca</th>
                                                <th class="text-end">Disponibilidad</th>
                                                <th>Tipo</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->products as $product)
                                                @php
                                                    $isSerialized = (bool) $product->category?->is_serialized;
                                                    $total = $isSerialized ? (int) ($product->assets_total ?? 0) : (int) ($product->qty_total ?? 0);
                                                    $unavailable = $isSerialized ? (int) ($product->assets_unavailable ?? 0) : 0;
                                                    $available = max($total - $unavailable, 0);
                                                @endphp
                                                <tr wire:key="inventory-search-product-{{ $product->id }}">
                                                    <td class="min-w-0">
                                                        <a
                                                            class="text-decoration-none fw-semibold"
                                                            href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                        >
                                                            {{ $product->name }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                                    <td>{{ $product->brand?->name ?? '—' }}</td>
                                                    <td class="text-end text-nowrap">
                                                        <div class="fw-semibold search-metric-number">{{ $available }}</div>
                                                        <div class="small text-body-secondary">
                                                            Total {{ $total }} · No disp. {{ $unavailable }}
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        @if ($isSerialized)
                                                            <x-ui.badge tone="info" variant="compact" :with-rail="false">Serializado</x-ui.badge>
                                                        @else
                                                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Por cantidad</x-ui.badge>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                            <a
                                                                class="btn btn-sm btn-outline-primary search-action-btn"
                                                                href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                            >
                                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                                Ver detalle
                                                            </a>
                                                            @if ($isSerialized)
                                                                <a
                                                                    class="btn btn-sm btn-outline-secondary search-action-btn"
                                                                    href="{{ route('inventory.products.assets.index', ['product' => $product->id]) }}"
                                                                >
                                                                    <i class="bi bi-hdd-stack me-1" aria-hidden="true"></i>
                                                                    Activos
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif
                    @endif
                @endif
            </x-ui.toolbar>
        </div>
    </div>
</div>
