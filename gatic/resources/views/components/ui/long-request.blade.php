@props([
    'target' => null,
])

<div
    class="gatic-long-request d-none position-absolute top-0 start-0 w-100 h-100 p-3 bg-white bg-opacity-75"
    data-gatic-long-request
    @if (is_string($target) && $target !== '')
        data-gatic-long-request-target="{{ $target }}"
    @endif
    role="status"
    aria-live="polite"
>
    <div class="d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-2">
            <div class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></div>
            <div class="fw-semibold">Cargando…</div>
        </div>

        <x-ui.skeleton variant="lines" :lines="3" />

        <div class="d-flex align-items-center justify-content-between">
            <div class="small text-muted">
                Esto est&aacute; tardando m&aacute;s de lo normal. Puedes cancelar y conservar lo que ya estabas viendo.
            </div>

            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                data-gatic-long-request-cancel
            >
                Cancelar
            </button>
        </div>
    </div>
</div>
