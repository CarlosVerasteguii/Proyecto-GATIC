<div class="container position-relative">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.products.index') }}">
                    Volver a Inventario
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    Historial de ajustes de inventario
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Actor</th>
                                    <th>Motivo</th>
                                    <th class="text-end">Entradas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($adjustments as $adjustment)
                                    <tr>
                                        <td class="text-nowrap">
                                            {{ $adjustment->created_at?->format('Y-m-d H:i') ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $adjustment->user?->name ?? '-' }}
                                        </td>
                                        <td class="text-break">
                                            {{ $adjustment->reason }}
                                        </td>
                                        <td class="text-end">
                                            {{ (int) ($adjustment->entries_count ?? 0) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">
                                            No hay ajustes registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $adjustments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

