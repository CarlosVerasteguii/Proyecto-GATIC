function safeJsonParse(value) {
    if (typeof value !== 'string' || value.trim() === '') return null;

    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
}

function extractErrorId(content) {
    const parsed = safeJsonParse(content);
    const errorId = parsed?.error_id;
    if (typeof errorId === 'string' && errorId.trim() !== '') return errorId.trim();
    return null;
}

let livewireHooksRegistered = false;

function registerLivewireHooks() {
    if (livewireHooksRegistered) return;
    if (!window.Livewire?.hook) return;

    livewireHooksRegistered = true;

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            if (typeof status !== 'number' || status < 500) return;

            const errorId = extractErrorId(content);
            if (!errorId) return;

            preventDefault();

            window.GaticToasts?.show?.({
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrió un error inesperado.',
                errorId,
            });
        });
    });
}

export function registerLivewireErrorHandling() {
    registerLivewireHooks();
    document.addEventListener('livewire:init', registerLivewireHooks);
}

