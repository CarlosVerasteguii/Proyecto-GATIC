<div class="container position-relative">
    <x-ui.long-request target="save,searchAssets,linkAsset,unlinkAsset" />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.detail-header
                :title="$isEdit ? 'Editar contrato' : 'Nuevo contrato'"
                :subtitle="$isEdit ? $identifier : 'Registrar un contrato de compra o arrendamiento'"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Contratos', 'url' => route('inventory.contracts.index')],
                        ['label' => $isEdit ? 'Editar' : 'Nuevo', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>
            </x-ui.detail-header>

            <form wire:submit="save">
                <div class="card">
                    <div class="card-header">
                        Datos del contrato
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="contract-identifier" class="form-label">Identificador *</label>
                                <input
                                    id="contract-identifier"
                                    type="text"
                                    class="form-control @error('identifier') is-invalid @enderror"
                                    placeholder="Ej: CTR-2026-001"
                                    wire:model="identifier"
                                />
                                @error('identifier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="contract-type" class="form-label">Tipo *</label>
                                <select
                                    id="contract-type"
                                    class="form-select @error('type') is-invalid @enderror"
                                    wire:model="type"
                                >
                                    <option value="">Seleccionar...</option>
                                    @foreach ($types as $typeOption)
                                        <option value="{{ $typeOption['value'] }}">{{ $typeOption['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="contract-supplier" class="form-label">Proveedor</label>
                                <select
                                    id="contract-supplier"
                                    class="form-select @error('supplier_id') is-invalid @enderror"
                                    wire:model="supplier_id"
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

                            <div class="col-12 col-md-3">
                                <label for="contract-start-date" class="form-label">Fecha de inicio</label>
                                <input
                                    id="contract-start-date"
                                    type="date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    wire:model="start_date"
                                />
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="contract-end-date" class="form-label">Fecha de fin</label>
                                <input
                                    id="contract-end-date"
                                    type="date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    wire:model="end_date"
                                />
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="contract-notes" class="form-label">Notas</label>
                                <textarea
                                    id="contract-notes"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Notas adicionales sobre el contrato..."
                                    wire:model="notes"
                                ></textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        Activos vinculados
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-8">
                                <label for="asset-search" class="form-label">Buscar activo para vincular</label>
                                <div class="input-group">
                                    <input
                                        id="asset-search"
                                        type="text"
                                        class="form-control"
                                        placeholder="Buscar por serial o asset tag..."
                                        wire:model="assetSearch"
                                        wire:keydown.enter.prevent="searchAssets"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        wire:click="searchAssets"
                                        wire:loading.attr="disabled"
                                        wire:target="searchAssets"
                                    >
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if (count($searchResults) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-gatic-head">
                                        <tr>
                                            <th>Serial</th>
                                            <th>Producto</th>
                                            <th>Contrato actual</th>
                                            <th class="text-end">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($searchResults as $result)
                                            <tr>
                                                <td>{{ $result['serial'] }}</td>
                                                <td>{{ $result['product_name'] }}</td>
                                                <td>
                                                    @if (!empty($result['current_contract_identifier']))
                                                        <span class="badge bg-warning text-dark">
                                                            {{ $result['current_contract_identifier'] }}
                                                        </span>
                                                        <small class="text-muted d-block">
                                                            Se reasignará si confirmas.
                                                        </small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-success"
                                                        wire:click="linkAsset({{ $result['id'] }})"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        <i class="bi bi-link-45deg"></i> Vincular
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($assetSearch !== '' && count($searchResults) === 0)
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                No se encontraron activos con ese criterio de busqueda.
                            </div>
                        @endif

                        @if (count($linkedAssets) > 0)
                            <h6 class="text-muted mb-2">Activos vinculados a este contrato:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Serial</th>
                                            <th>Producto</th>
                                            <th class="text-end">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($linkedAssets as $asset)
                                            <tr>
                                                <td>{{ $asset['serial'] }}</td>
                                                <td>{{ $asset['product_name'] }}</td>
                                                <td class="text-end">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="unlinkAsset({{ $asset['id'] }})"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        <i class="bi bi-x-lg"></i> Desvincular
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                No hay activos vinculados a este contrato.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <a href="{{ route('inventory.contracts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                    </a>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Actualizar' : 'Crear' }} contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
