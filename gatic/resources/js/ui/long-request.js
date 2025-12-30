function getThresholdMs() {
    const el = document.querySelector('[data-testid="app-toasts"]');
    return Number(el?.dataset?.longRequestThresholdMs ?? 3000);
}

function getComponentRoot(componentId) {
    return document.querySelector(`[wire\\:id="${componentId}"]`);
}

function findOverlay(componentId) {
    const root = getComponentRoot(componentId);
    if (!root) return null;
    return root.querySelector('[data-gatic-long-request]');
}

function getClosestComponentId(fromEl) {
    const root = fromEl.closest('[wire\\:id]');
    const id = root?.getAttribute('wire:id');
    return id || null;
}

function safeJsonParse(value) {
    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
}

function extractComponentIdsFromRequestPayload(payload) {
    const ids = [];
    if (typeof payload !== 'string' || payload.trim() === '') return ids;

    const parsed = safeJsonParse(payload);
    const components = parsed?.components;
    if (!Array.isArray(components)) return ids;

    for (const c of components) {
        const snapshot = typeof c?.snapshot === 'string' ? safeJsonParse(c.snapshot) : null;
        const id = snapshot?.memo?.id;
        if (typeof id === 'string' && id.trim() !== '') ids.push(id);
    }

    return ids;
}

function extractCallMethodsByComponentId(payload) {
    const result = new Map();
    if (typeof payload !== 'string' || payload.trim() === '') return result;

    const parsed = safeJsonParse(payload);
    const components = parsed?.components;
    if (!Array.isArray(components)) return result;

    for (const c of components) {
        const snapshot = typeof c?.snapshot === 'string' ? safeJsonParse(c.snapshot) : null;
        const id = snapshot?.memo?.id;
        if (typeof id !== 'string' || id.trim() === '') continue;

        const calls = Array.isArray(c?.calls) ? c.calls : [];
        const methods = calls
            .map((call) => call?.method)
            .filter((method) => typeof method === 'string' && method.trim() !== '')
            .map((method) => method.trim());

        result.set(id, methods);
    }

    return result;
}

const activeControllersByComponentId = new Map();
const timersByComponentId = new Map();

function clearOverlayTimer(componentId) {
    const current = timersByComponentId.get(componentId);
    if (current) window.clearTimeout(current);
    timersByComponentId.delete(componentId);
}

function setOverlayVisible(componentId, visible) {
    const overlay = findOverlay(componentId);
    if (!(overlay instanceof HTMLElement)) return;

    overlay.classList.toggle('d-none', !visible);
}

function cancelComponentRequest(componentId) {
    const controller = activeControllersByComponentId.get(componentId);
    if (!controller) return false;

    clearOverlayTimer(componentId);
    controller.abort();
    activeControllersByComponentId.delete(componentId);
    setOverlayVisible(componentId, false);

    return true;
}

function registerCancelClickHandler() {
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.matches('[data-gatic-long-request-cancel]')) return;

        const componentId = getClosestComponentId(target);
        if (!componentId) return;

        const cancelled = cancelComponentRequest(componentId);
        if (!cancelled) return;

        window.GaticToasts?.show?.({
            type: 'info',
            title: 'Cancelado',
            message: 'Se canceló la operación y se conservó el estado anterior.',
            timeoutMs: 5000,
        });
    });
}

let livewireHooksRegistered = false;

function registerLivewireHooks() {
    if (livewireHooksRegistered) return;
    if (!window.Livewire?.hook) return;

    livewireHooksRegistered = true;
    registerCancelClickHandler();

    window.Livewire.hook('request', ({ options, payload, respond, fail }) => {
        const controller = new AbortController();
        options.signal = controller.signal;

        const thresholdMs = getThresholdMs();
        const callMethodsByComponentId = extractCallMethodsByComponentId(payload);
        const componentIds = extractComponentIdsFromRequestPayload(payload);
        componentIds.forEach((id) => {
            activeControllersByComponentId.set(id, controller);
            clearOverlayTimer(id);
            setOverlayVisible(id, false);

            const overlay = findOverlay(id);
            if (!overlay) return;

            const targetAttr = overlay.getAttribute('data-gatic-long-request-target');
            if (typeof targetAttr === 'string' && targetAttr.trim() !== '') {
                const targets = new Set(
                    targetAttr
                        .split(',')
                        .map((t) => t.trim())
                        .filter((t) => t !== ''),
                );
                const methods = callMethodsByComponentId.get(id) ?? [];
                const matchesTarget = methods.some((m) => targets.has(m));
                if (!matchesTarget) return;
            }

            const timeout = window.setTimeout(() => setOverlayVisible(id, true), thresholdMs);
            timersByComponentId.set(id, timeout);
        });

        respond(() => {
            componentIds.forEach((id) => {
                if (activeControllersByComponentId.get(id) === controller) {
                    activeControllersByComponentId.delete(id);
                }
                clearOverlayTimer(id);
                setOverlayVisible(id, false);
            });
        });

        fail(({ preventDefault }) => {
            if (controller.signal.aborted) {
                preventDefault();
            }
            componentIds.forEach((id) => {
                if (activeControllersByComponentId.get(id) === controller) {
                    activeControllersByComponentId.delete(id);
                }
                clearOverlayTimer(id);
                setOverlayVisible(id, false);
            });
        });
    });
}

export function registerLongRequestUi() {
    registerLivewireHooks();
    document.addEventListener('livewire:init', registerLivewireHooks);
}
