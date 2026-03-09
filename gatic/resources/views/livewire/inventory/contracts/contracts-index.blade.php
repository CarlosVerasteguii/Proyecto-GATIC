<div class="container position-relative">
    <x-ui.long-request />

    @php
        $resultsCount = $contracts->total();
        $hasFilters = $this->hasActiveFilters();
        $activeFiltersCount = collect([
            trim($search) !== '' ? 'search' : null,
            $typeFilter !== '' ? 'type' : null,
            $supplierFilter !== '' ? 'supplier' : null,
        ])->filter()->count();
        $selectedSupplier = collect($suppliers)->firstWhere('id', (int) $supplierFilter);
        $selectedSupplierName = is_array($selectedSupplier) ? (string) ($selectedSupplier['name'] ?? '') : '';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <x-ui.toolbar
                title="Contratos"
                subtitle="Gestiona contratos de compra y arrendamiento con el mismo lenguaje visual del shell, alerts y search."
                filterId="contracts-filters"
                searchColClass="col-12 col-lg-5"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Inventario', 'url' => route('inventory.products.index')],
                        ['label' => 'Contratos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                        Contratos <strong>{{ number_format($resultsCount) }}</strong>
                    </x-ui.badge>

                    @if ($activeFiltersCount > 0)
                        <x-ui.badge tone="info" variant="compact" :with-rail="false">
                            Filtros <strong>{{ $activeFiltersCount }}</strong>
                        </x-ui.badge>
                    @endif

                    <a class="btn btn-sm btn-primary" href="{{ route('inventory.contracts.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                        Nuevo contrato
                    </a>
                </x-slot:actions>

                <x-slot:search>
                    <div>
                        <label for="contracts-search" class="form-label">Buscar por identificador</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                id="contracts-search"
                                type="search"
                                class="form-control"
                                placeholder="Ej: CTR-2026-001"
                                wire:model.live.debounce.300ms="search"
                                autocomplete="off"
                                spellcheck="false"
                            />
                        </div>
                        <div class="form-text">
                            Usa el identificador para encontrar rápido un contrato específico.
                        </div>
                    </div>
                </x-slot:search>

                <x-slot:filters>
                    <div class="col-12 col-md-3">
                        <label for="contracts-type-filter" class="form-label">Tipo</label>
                        <select
                            id="contracts-type-filter"
                            class="form-select"
                            wire:model.live="typeFilter"
                        >
                            <option value="">Todos</option>
                            <option value="purchase">Compra</option>
                            <option value="lease">Arrendamiento</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="contracts-supplier-filter" class="form-label">Proveedor</label>
                        <select
                            id="contracts-supplier-filter"
                            class="form-select"
                            wire:model.live="supplierFilter"
                        >
                            <option value="">Todos</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot:filters>

                <x-slot:clearFilters>
                    @if ($hasFilters)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearFilters"
                            aria-label="Limpiar filtros de contratos"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                            Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>

                <div class="d-flex flex-column gap-3 mb-3">
                    @if ($hasFilters)
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-body-secondary">Filtros activos:</span>

                            @if (trim($search) !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Búsqueda: {{ $search }}
                                </x-ui.badge>
                            @endif

                            @if ($typeFilter === 'purchase')
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">Tipo: Compra</x-ui.badge>
                            @elseif ($typeFilter === 'lease')
                                <x-ui.badge tone="warning" variant="compact" :with-rail="false">Tipo: Arrendamiento</x-ui.badge>
                            @endif

                            @if ($selectedSupplierName !== '')
                                <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                    Proveedor: {{ $selectedSupplierName }}
                                </x-ui.badge>
                            @endif
                        </div>
                    @endif

                    <div class="small text-body-secondary">
                        Revisa vigencia, proveedor y cantidad de activos vinculados sin salir del listado.
                    </div>
                </div>

                <div class="small text-body-secondary mb-2">
                    Mostrando {{ number_format($resultsCount) }} contrato{{ $resultsCount === 1 ? '' : 's' }}.
                </div>

                <div class="table-responsive-xl border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 table-gatic-head">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Tipo</th>
                                <th>Proveedor</th>
                                <th>Vigencia</th>
                                <th class="text-center">Activos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contracts as $contract)
                                @php
                                    $typeTone = $contract->type === \App\Models\Contract::TYPE_PURCHASE ? 'info' : 'warning';
                                    $hasDates = $contract->start_date || $contract->end_date;
                                @endphp
                                <tr wire:key="contract-row-{{ $contract->id }}">
                                    <td class="min-w-0">
                                        <div class="min-w-0">
                                            <a
                                                href="{{ route('inventory.contracts.show', ['contract' => $contract->id]) }}"
                                                class="fw-semibold text-decoration-none"
                                            >
                                                {{ $contract->identifier }}
                                            </a>
                                            <div class="small text-body-secondary text-truncate">
                                                {{ $contract->notes ? \Illuminate\Support\Str::limit($contract->notes, 90) : 'Sin notas operativas capturadas.' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <x-ui.badge :tone="$typeTone" variant="compact" :with-rail="false">
                                            {{ $contract->type_label }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="min-w-0">
                                        <div class="text-truncate">{{ $contract->supplier?->name ?? 'Sin proveedor asignado' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($hasDates)
                                            <div class="fw-semibold">
                                                {{ $contract->start_date?->format('d/m/Y') ?? 'Sin inicio' }}
                                            </div>
                                            <div class="small text-body-secondary">
                                                al {{ $contract->end_date?->format('d/m/Y') ?? 'sin fin' }}
                                            </div>
                                        @else
                                            <span class="text-body-secondary">Sin fechas definidas</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                                            {{ $contract->assets_count }}
                                        </x-ui.badge>
                                        <div class="small text-body-secondary mt-1">vinculados</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a
                                                href="{{ route('inventory.contracts.show', ['contract' => $contract->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>
                                                Ver
                                            </a>
                                            <a
                                                href="{{ route('inventory.contracts.edit', ['contract' => $contract->id]) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        @if ($hasFilters)
                                            <x-ui.empty-state variant="filter" compact />
                                        @else
                                            <x-ui.empty-state
                                                icon="bi-file-earmark-text"
                                                title="No hay contratos registrados"
                                                description="Crea el primer contrato para empezar a vincular activos de compra o arrendamiento."
                                                compact
                                            >
                                                <a href="{{ route('inventory.contracts.create') }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                                                    Crear primer contrato
                                                </a>
                                            </x-ui.empty-state>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $contracts->links() }}
                </div>
            </x-ui.toolbar>
        </div>
    </div>
</div>
