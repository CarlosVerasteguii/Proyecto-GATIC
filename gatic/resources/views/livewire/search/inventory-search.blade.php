<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <span>Buscar en Inventario</span>
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="submitSearch" class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-8 col-lg-6">
                            <label for="inventory-search" class="form-label">
                                Buscar por nombre de producto, serial o asset tag
                            </label>
                            <input
                                id="inventory-search"
                                type="text"
                                class="form-control"
                                placeholder="Ej: Laptop Dell, SN12345, GATIC-001..."
                                wire:model.defer="search"
                                wire:loading.attr="disabled"
                                wire:target="submitSearch"
                                autofocus
                            />
                            <div class="form-text">
                                Presiona Enter o clic en "Buscar". Para nombres, intenta comenzar desde el inicio (ej: "Laptop Dell").
                            </div>
                        </div>
                        <div class="col-12 col-md-auto">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    wire:loading.attr="disabled"
                                    wire:target="submitSearch"
                                >
                                    <span wire:loading.remove wire:target="submitSearch">
                                        <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
                                    </span>
                                    <span wire:loading.inline wire:target="submitSearch">
                                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                        Buscando...
                                    </span>
                                </button>

                                @if ($this->search !== '')
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        wire:click="clearSearch"
                                        wire:loading.attr="disabled"
                                        wire:target="submitSearch,clearSearch"
                                    >
                                        Limpiar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>

                    @if ($this->showMinCharsMessage)
                        <div class="alert alert-info mb-3" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            Ingresa al menos {{ $minChars }} caracteres para buscar.
                        </div>
                    @elseif ($this->showNoResultsMessage)
                        <div class="alert alert-warning mb-3" role="alert">
                            <i class="bi bi-search me-1"></i>
                            No se encontraron resultados para "<strong>{{ $this->search }}</strong>".
                        </div>
                    @elseif ($this->search === '')
                        <div class="text-muted mb-3">
                            <i class="bi bi-lightbulb me-1"></i>
                            Tip: Ingresa un serial o asset tag exacto para ir directamente al activo.
                        </div>
                    @endif

                    @if ($this->assets->isNotEmpty())
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-hdd me-1"></i>
                                Activos
                                <span class="badge bg-secondary">{{ $this->assets->count() }}</span>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Serial</th>
                                            <th>Asset Tag</th>
                                            <th>Producto</th>
                                            <th>Estado</th>
                                            <th>Ubicación</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->assets as $asset)
                                            <tr>
                                                <td>
                                                    <code>{{ $asset->serial }}</code>
                                                </td>
                                                <td>
                                                    @if ($asset->asset_tag)
                                                        <code>{{ $asset->asset_tag }}</code>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a
                                                        class="text-decoration-none"
                                                        href="{{ route('inventory.products.show', ['product' => $asset->product_id]) }}"
                                                    >
                                                        {{ $asset->product?->name ?? '-' }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <x-ui.status-badge :status="$asset->status" />
                                                </td>
                                                <td>{{ $asset->location?->name ?? '-' }}</td>
                                                <td class="text-end">
                                                    <a
                                                        class="btn btn-sm btn-outline-primary"
                                                        href="{{ route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]) }}"
                                                    >
                                                        Ver detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($this->products->isNotEmpty())
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-box me-1"></i>
                                Productos
                                <span class="badge bg-secondary">{{ $this->products->count() }}</span>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Categoría</th>
                                            <th>Marca</th>
                                            <th>Tipo</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->products as $product)
                                            <tr>
                                                <td>
                                                    <a
                                                        class="text-decoration-none"
                                                        href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                    >
                                                        {{ $product->name }}
                                                    </a>
                                                </td>
                                                <td>{{ $product->category?->name ?? '-' }}</td>
                                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                                <td>
                                                    @if ($product->category?->is_serialized)
                                                        <span class="badge bg-info">Serializado</span>
                                                    @else
                                                        <span class="badge bg-secondary">Por cantidad</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a
                                                        class="btn btn-sm btn-outline-primary"
                                                        href="{{ route('inventory.products.show', ['product' => $product->id]) }}"
                                                    >
                                                        Ver detalle
                                                    </a>
                                                    @if ($product->category?->is_serialized)
                                                        <a
                                                            class="btn btn-sm btn-outline-secondary"
                                                            href="{{ route('inventory.products.assets.index', ['product' => $product->id]) }}"
                                                        >
                                                            Activos
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
