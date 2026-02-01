@props([
    'table' => '',
    'buttonClass' => 'btn btn-sm btn-outline-secondary dropdown-toggle',
])

@if ($table)
    <div class="dropdown" data-column-manager="{{ $table }}">
        <button
            type="button"
            class="{{ $buttonClass }}"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
        >
            <i class="bi bi-layout-three-columns me-1" aria-hidden="true"></i>
            Columnas
        </button>

        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 18rem;">
            <div class="small text-muted mb-2">Mostrar/ocultar columnas</div>
            <div class="d-flex flex-column gap-2" data-column-manager-list></div>

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary w-100 mt-3"
                data-column-manager-reset
            >
                Restaurar
            </button>
        </div>
    </div>
@endif

