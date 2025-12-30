function getContainer() {
    return document.querySelector('[data-testid="app-toasts"]');
}

function getDelayDefaults() {
    const container = getContainer();

    return {
        defaultDelayMs: Number(container?.dataset?.toastDefaultDelayMs ?? 5000),
        undoDelayMs: Number(container?.dataset?.toastUndoDelayMs ?? 10000),
    };
}

function normalizeToastPayload(payload) {
    if (Array.isArray(payload) && payload.length > 0 && typeof payload[0] === 'object') {
        return payload[0] ?? {};
    }

    if (payload && typeof payload === 'object') return payload;

    return {};
}

function coerceType(type) {
    const allowed = new Set(['success', 'error', 'info', 'warning']);
    if (!allowed.has(type)) return 'info';
    return type;
}

function buildToastElement({ type, title, message, timeoutMs, errorId, action }) {
    const { defaultDelayMs, undoDelayMs } = getDelayDefaults();
    const resolvedType = coerceType(type);
    const hasAction = action && typeof action === 'object' && typeof action.label === 'string' && typeof action.event === 'string';

    const resolvedTimeoutMs = Number(timeoutMs ?? (hasAction ? undoDelayMs : defaultDelayMs));
    const resolvedTitle = typeof title === 'string' && title.trim() !== '' ? title.trim() : null;
    const resolvedMessage = typeof message === 'string' ? message.trim() : '';
    const resolvedErrorId = typeof errorId === 'string' && errorId.trim() !== '' ? errorId.trim() : null;

    const el = document.createElement('div');
    el.className = `toast gatic-toast text-bg-${resolvedType === 'error' ? 'danger' : resolvedType} border-0`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', resolvedType === 'error' ? 'assertive' : 'polite');
    el.setAttribute('aria-atomic', 'true');
    el.dataset.bsDelay = String(resolvedTimeoutMs);
    el.dataset.bsAutohide = resolvedTimeoutMs > 0 ? 'true' : 'false';

    const header = document.createElement('div');
    header.className = 'toast-header';

    const strong = document.createElement('strong');
    strong.className = 'me-auto';
    strong.textContent = resolvedTitle ?? {
        success: 'Éxito',
        error: 'Error',
        info: 'Info',
        warning: 'Aviso',
    }[resolvedType];

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close ms-2 mb-1';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'Cerrar');

    header.appendChild(strong);
    header.appendChild(close);

    const body = document.createElement('div');
    body.className = 'toast-body';

    const messageEl = document.createElement('div');
    messageEl.textContent = resolvedMessage;
    body.appendChild(messageEl);

    if (resolvedErrorId) {
        const idEl = document.createElement('div');
        idEl.className = 'mt-1 small opacity-75';
        idEl.textContent = `ID: ${resolvedErrorId}`;
        body.appendChild(idEl);
    }

    if (hasAction) {
        const actionsEl = document.createElement('div');
        actionsEl.className = 'mt-2 d-flex gap-2';

        const actionBtn = document.createElement('button');
        actionBtn.type = 'button';
        actionBtn.className = 'btn btn-light btn-sm';
        actionBtn.textContent = action.label;
        actionBtn.dataset.gaticToastActionEvent = action.event;
        actionBtn.dataset.gaticToastActionParams = JSON.stringify(action.params ?? {});

        actionsEl.appendChild(actionBtn);
        body.appendChild(actionsEl);
    }

    el.appendChild(header);
    el.appendChild(body);

    return el;
}

function showToast(payload) {
    const container = getContainer();
    if (!container) return;

    const p = normalizeToastPayload(payload);
    const el = buildToastElement(p);

    container.appendChild(el);

    const toast = new window.bootstrap.Toast(el);
    el.addEventListener('hidden.bs.toast', () => el.remove(), { once: true });

    el.addEventListener('click', (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;
        const actionEvent = target.dataset?.gaticToastActionEvent;
        const actionParams = target.dataset?.gaticToastActionParams;
        if (!actionEvent) return;

        let params = {};
        try {
            params = JSON.parse(actionParams ?? '{}');
        } catch {
            params = {};
        }

        if (window.Livewire?.dispatch) {
            window.Livewire.dispatch(actionEvent, params);
        }

        toast.hide();
    });

    toast.show();
}

function showFlashToasts() {
    const container = getContainer();
    if (!container) return;

    let flash = [];
    try {
        flash = JSON.parse(container.dataset.flashToasts ?? '[]');
    } catch {
        flash = [];
    }

    if (!Array.isArray(flash) || flash.length === 0) return;

    flash.forEach((payload) => showToast(payload));
}

let livewireListenerRegistered = false;

function registerLivewireToastsListener() {
    if (livewireListenerRegistered) return;
    if (!window.Livewire?.on) return;

    livewireListenerRegistered = true;
    window.Livewire.on('ui:toast', (payload) => {
        if (window.bootstrap?.Toast) showToast(payload);
    });
}

export function registerToasts() {
    window.GaticToasts = window.GaticToasts ?? {};
    window.GaticToasts.show = showToast;

    document.addEventListener('DOMContentLoaded', () => {
        if (window.bootstrap?.Toast) showFlashToasts();
    });

    registerLivewireToastsListener();
    document.addEventListener('livewire:init', registerLivewireToastsListener);
}
