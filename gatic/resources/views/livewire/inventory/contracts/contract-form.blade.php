<div class="container position-relative">
    <x-ui.long-request target="save,searchAssets,linkAsset,unlinkAsset" />

    @php
        $linkedAssetsCount = count($linkedAssets);
        $searchResultsCount = count($searchResults);
        $formatUiDate = static function (?string $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return $value;
            }
        };
        $typeTone = match ($type) {
            \App\Models\Contract::TYPE_PURCHASE => 'info',
            \App\Models\Contract::TYPE_LEASE => 'warning',
            default => 'neutral',
        };
        $typeLabel = collect($types)->firstWhere('value', $type)['label'] ?? 'Tipo pendiente';
        $vigencyLabel = $start_date || $end_date
            ? trim(($start_date ? $formatUiDate($start_date) : 'Sin inicio')
                .' al '
                .($end_date ? $formatUiDate($end_date) : 'sin fin'))
            : 'Sin fechas definidas';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.detail-header
                :title="$isEdit ? 'Editar contrato' : 'Nuevo contrato'"
                :subtitle="$isEdit ? $identifier : 'Registra un contrato de compra o arrendamiento y vincula sus activos en el mismo flujo.'"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Contratos', 'url' => route('inventory.contracts.index')],
                        ['label' => $isEdit ? 'Editar' : 'Nuevo', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        {{ $isEdit ? 'Edición' : 'Borrador' }}
                    </x-ui.badge>
                    <x-ui.badge :tone="$typeTone" variant="compact" :with-rail="false">
                        {{ $typeLabel }}
                    </x-ui.badge>
                </x-slot:status>

                <x-slot:kpis>
                    <x-ui.detail-header-kpi label="Activos vinculados" :value="$linkedAssetsCount" />
                    <x-ui.detail-header-kpi label="Coincidencias" :value="$searchResultsCount" variant="info" />
                </x-slot:kpis>

                <x-slot:actions>
                    <a href="{{ route('inventory.contracts.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver a contratos
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <form wire:submit="save">
                <x-ui.section-card
                    title="Datos del contrato"
                    subtitle="Captura el identificador, tipo, proveedor y vigencia antes de guardar."
                    icon="bi-file-earmark-text"
                    class="mb-4"
                >
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Modo</div>
                                <div class="mt-2">
                                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                        {{ $isEdit ? 'Edición en curso' : 'Nuevo contrato' }}
                                    </x-ui.badge>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Tipo</div>
                                <div class="mt-2">
                                    <x-ui.badge :tone="$typeTone" variant="compact" :with-rail="false">
                                        {{ $typeLabel }}
                                    </x-ui.badge>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Vigencia</div>
                                <div class="fw-semibold mt-2">{{ $vigencyLabel }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded-3 h-100 p-3 bg-body-tertiary">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Activos vinculados</div>
                                <div class="fw-semibold mt-2">{{ number_format($linkedAssetsCount) }}</div>
                                <div class="small text-body-secondary mt-2">
                                    {{ $linkedAssetsCount === 0 ? 'Todavía no hay activos asociados.' : 'Se guardarán junto con el contrato.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label for="contract-identifier" class="form-label">Identificador <span class="text-danger">*</span></label>
                            <input
                                id="contract-identifier"
                                type="text"
                                class="form-control @error('identifier') is-invalid @enderror"
                                placeholder="Ej: CTR-2026-001"
                                wire:model.blur="identifier"
                                autocomplete="off"
                            />
                            @error('identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="contract-type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select
                                id="contract-type"
                                class="form-select @error('type') is-invalid @enderror"
                                wire:model.live="type"
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

                        <div class="col-12 col-lg-6">
                            <label for="contract-supplier" class="form-label">Proveedor</label>
                            <livewire:ui.supplier-combobox wire:model="supplier_id" inputId="contract-supplier" />
                            <div class="form-text">Opcional. Úsalo cuando necesites rastrear el origen del contrato.</div>
                            @error('supplier_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="contract-start-date" class="form-label">Fecha de inicio</label>
                            <input
                                id="contract-start-date"
                                type="date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                wire:model.blur="start_date"
                            />
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="contract-end-date" class="form-label">Fecha de fin</label>
                            <input
                                id="contract-end-date"
                                type="date"
                                class="form-control @error('end_date') is-invalid @enderror"
                                wire:model.blur="end_date"
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
                                rows="4"
                                placeholder="Notas operativas, alcance del contrato o cualquier contexto útil para inventario."
                                wire:model.blur="notes"
                            ></textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card
                    title="Activos vinculados"
                    subtitle="Busca por serial o asset tag, revisa si ya pertenecen a otro contrato y decide si quieres reasignarlos."
                    icon="bi-link-45deg"
                    class="mb-4"
                >
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle mt-1" aria-hidden="true"></i>
                        <div>
                            Vincular o reasignar activos cambia el contexto contractual del inventario. Guarda el formulario para aplicar los cambios.
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-xl-8">
                            <label for="asset-search" class="form-label">Buscar activo para vincular</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    id="asset-search"
                                    type="search"
                                    class="form-control"
                                    placeholder="Ej: SN-001 o GATIC-001"
                                    wire:model="assetSearch"
                                    wire:keydown.enter.prevent="searchAssets"
                                    autocomplete="off"
                                    spellcheck="false"
                                />
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    wire:click="searchAssets"
                                    wire:loading.attr="disabled"
                                    wire:target="searchAssets"
                                >
                                    <span wire:loading.remove wire:target="searchAssets">
                                        Buscar
                                    </span>
                                    <span wire:loading.inline wire:target="searchAssets">
                                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                        Buscando...
                                    </span>
                                </button>
                            </div>
                            <div class="form-text">
                                Se excluyen activos eliminados y los que ya agregaste a este formulario.
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="border rounded-3 p-3 bg-body-tertiary h-100">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Resultado de búsqueda</div>
                                <div class="fw-semibold mt-2">
                                    {{ $assetSearch !== '' ? number_format($searchResultsCount) : '—' }}
                                </div>
                                <div class="small text-body-secondary mt-2">
                                    {{ $assetSearch !== '' ? 'Coincidencias listas para vincular o revisar.' : 'Ingresa un serial o asset tag para comenzar.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($assetSearch !== '' && $searchResultsCount === 0)
                        <div class="border rounded-3 p-4 mb-4">
                            <x-ui.empty-state
                                variant="filter"
                                icon="bi-search"
                                title="No se encontraron activos"
                                description="Prueba con un serial completo o con el asset tag exacto del activo."
                                compact
                            />
                        </div>
                    @elseif ($searchResultsCount > 0)
                        <div class="table-responsive-xl border rounded-3 mb-4">
                            <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                <thead>
                                    <tr>
                                        <th>Activo</th>
                                        <th>Contrato actual</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($searchResults as $result)
                                        <tr wire:key="contract-search-result-{{ $result['id'] }}">
                                            <td class="min-w-0">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate">{{ $result['product_name'] }}</div>
                                                    <div class="small text-body-secondary text-break">
                                                        Serial: <code>{{ $result['serial'] }}</code>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                @if (! empty($result['current_contract_identifier']))
                                                    <x-ui.badge tone="warning" variant="compact" :with-rail="false">
                                                        {{ $result['current_contract_identifier'] }}
                                                    </x-ui.badge>
                                                    <div class="small text-body-secondary mt-2">
                                                        Se reasignará si confirmas con una segunda acción.
                                                    </div>
                                                @else
                                                    <span class="text-body-secondary">Sin contrato asignado</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    wire:click="linkAsset({{ $result['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="linkAsset"
                                                >
                                                    <i class="bi bi-link-45deg me-1" aria-hidden="true"></i>
                                                    Vincular
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h6 mb-1">Activos que se guardarán con este contrato</h2>
                            <p class="small text-body-secondary mb-0">
                                Revisa el contexto final antes de crear o actualizar el contrato.
                            </p>
                        </div>

                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                            {{ number_format($linkedAssetsCount) }} activo{{ $linkedAssetsCount === 1 ? '' : 's' }}
                        </x-ui.badge>
                    </div>

                    @if ($linkedAssetsCount === 0)
                        <div class="border rounded-3 p-4">
                            <x-ui.empty-state
                                icon="bi-link-45deg"
                                title="No hay activos vinculados"
                                description="Puedes guardar el contrato sin activos o buscar uno para asociarlo desde esta misma pantalla."
                                compact
                            />
                        </div>
                    @else
                        <div class="table-responsive-xl border rounded-3">
                            <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                                <thead>
                                    <tr>
                                        <th>Activo</th>
                                        <th>Producto</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($linkedAssets as $asset)
                                        <tr wire:key="linked-contract-asset-{{ $asset['id'] }}">
                                            <td class="text-nowrap">
                                                <code>{{ $asset['serial'] }}</code>
                                            </td>
                                            <td class="min-w-0">
                                                <div class="text-truncate">{{ $asset['product_name'] }}</div>
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="unlinkAsset({{ $asset['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="unlinkAsset"
                                                >
                                                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                                                    Desvincular
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-ui.section-card>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <a href="{{ route('inventory.contracts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>
                            {{ $isEdit ? 'Actualizar contrato' : 'Crear contrato' }}
                        </span>
                        <span wire:loading.inline wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            Guardando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
