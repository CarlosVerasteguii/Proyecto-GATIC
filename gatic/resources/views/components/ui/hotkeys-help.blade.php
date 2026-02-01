{{--
    Hotkeys Help Modal
    ------------------
    Modal showing available keyboard shortcuts.

    Usage:
        Include once in your layout:
        <x-ui.hotkeys-help />

    Press ? to open this modal.
--}}
<div
    id="hotkeys-help-modal"
    class="modal fade"
    tabindex="-1"
    aria-labelledby="hotkeys-help-title"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hotkeys-help-title">
                    <i class="bi bi-keyboard me-2" aria-hidden="true"></i>
                    Atajos de teclado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    {{-- Navigation --}}
                    <div class="col-12">
                        <h6 class="text-muted text-uppercase small fw-semibold mb-3">Navegación</h6>
                        <dl class="row mb-0 small">
                            <dt class="col-4"><kbd>/</kbd></dt>
                            <dd class="col-8">Enfocar búsqueda</dd>

                            <dt class="col-4"><kbd>Ctrl</kbd> + <kbd>K</kbd></dt>
                            <dd class="col-8">Abrir paleta de comandos</dd>

                            <dt class="col-4"><kbd>j</kbd> / <kbd>↓</kbd></dt>
                            <dd class="col-8">Siguiente fila</dd>

                            <dt class="col-4"><kbd>k</kbd> / <kbd>↑</kbd></dt>
                            <dd class="col-8">Fila anterior</dd>

                            <dt class="col-4"><kbd>Enter</kbd></dt>
                            <dd class="col-8">Abrir detalle de fila</dd>

                            <dt class="col-4"><kbd>[</kbd></dt>
                            <dd class="col-8">Toggle sidebar</dd>
                        </dl>
                    </div>

                    {{-- Actions --}}
                    <div class="col-12">
                        <h6 class="text-muted text-uppercase small fw-semibold mb-3">Acciones</h6>
                        <dl class="row mb-0 small">
                            <dt class="col-4"><kbd>Ctrl</kbd> + <kbd>Enter</kbd></dt>
                            <dd class="col-8">Guardar formulario</dd>

                            <dt class="col-4"><kbd>Esc</kbd></dt>
                            <dd class="col-8">Cerrar modal / Salir de campo</dd>

                            <dt class="col-4"><kbd>?</kbd></dt>
                            <dd class="col-8">Mostrar esta ayuda</dd>
                        </dl>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        En Mac, usa <kbd>Cmd</kbd> en lugar de <kbd>Ctrl</kbd>.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    #hotkeys-help-modal kbd {
        display: inline-block;
        padding: 0.2em 0.4em;
        font-size: 0.8em;
        font-family: var(--bs-font-monospace);
        background-color: var(--bs-gray-200);
        border: 1px solid var(--bs-gray-400);
        border-radius: 0.25rem;
        box-shadow: inset 0 -1px 0 var(--bs-gray-400);
    }

    #hotkeys-help-modal dl {
        line-height: 2;
    }

    #hotkeys-help-modal dt {
        text-align: right;
    }
</style>
