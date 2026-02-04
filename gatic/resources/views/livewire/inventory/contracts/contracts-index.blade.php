<div class="container position-relative">
    <x-ui.long-request />

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <x-ui.detail-header title="Contratos" subtitle="Gestiona contratos de compra y arrendamiento">
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Contratos', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    <a class="btn btn-sm btn-primary" href="{{ route('inventory.contracts.create') }}">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo contrato
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <div class="card">
                <div class="card-header">
                    Listado de contratos
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-4">
                            <label for="contracts-search" class="form-label">Buscar</label>
                            <input
                                id="contracts-search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por identificador."
                                wire:model.live.debounce.300ms="search"
                            />
                        </div>

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
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Identificador</th>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th>Vigencia</th>
                                    <th class="text-center">Activos</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contracts as $contract)
                                    <tr>
                                        <td>
                                            <a href="{{ route('inventory.contracts.show', ['contract' => $contract->id]) }}" class="text-decoration-none">
                                                {{ $contract->identifier }}
                                            </a>
                                        </td>
                                        <td>{{ $contract->type_label }}</td>
                                        <td>{{ $contract->supplier?->name ?? '-' }}</td>
                                        <td>
                                            @if ($contract->start_date || $contract->end_date)
                                                {{ $contract->start_date?->format('d/m/Y') ?? '—' }}
                                                al
                                                {{ $contract->end_date?->format('d/m/Y') ?? '—' }}
                                            @else
                                                Sin fechas
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $contract->assets_count }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a
                                                href="{{ route('inventory.contracts.show', ['contract' => $contract->id]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Ver
                                            </a>
                                            <a
                                                href="{{ route('inventory.contracts.edit', ['contract' => $contract->id]) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Editar
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted text-center">
                                            <div class="py-4">
                                                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 text-secondary"></i>
                                                No hay contratos registrados.
                                                <div class="mt-2">
                                                    <a href="{{ route('inventory.contracts.create') }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear primer contrato
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $contracts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
