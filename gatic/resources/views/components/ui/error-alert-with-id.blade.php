@props([
    'message' => null,
    'errorId' => null,
])

<div class="alert alert-danger" role="alert" data-testid="error-alert-with-id">
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div>
            <div class="fw-semibold">
                {{ $message ?? 'Ocurrió un error inesperado.' }}
            </div>

            @if (is_string($errorId) && $errorId !== '')
                <div class="mt-2 small">
                    <span class="opacity-75">ID:</span>
                    <code class="ms-1" data-testid="error-id">{{ $errorId }}</code>
                </div>
            @endif
        </div>

        @if (is_string($errorId) && $errorId !== '')
            <button
                type="button"
                class="btn btn-outline-dark btn-sm"
                data-copy-to-clipboard
                data-copy-text="{{ $errorId }}"
                data-testid="copy-error-id"
            >
                Copiar
            </button>
        @endif
    </div>
</div>

