const UPDATE_ENDPOINT = '/me/ui-preferences';
const debounceTimers = new Map();

function getBootstrapPrefs() {
    const prefs = window.gaticUserPrefs;

    if (!prefs || typeof prefs !== 'object') {
        return {};
    }

    return prefs;
}

function getCsrfToken() {
    const tokenEl = document.querySelector('meta[name="csrf-token"]');
    const token = tokenEl?.getAttribute('content') ?? '';

    return token.trim();
}

function sendPreferenceUpdate(key, value) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        return;
    }

    fetch(UPDATE_ENDPOINT, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ key, value }),
    }).catch(() => {
        // Silent failure by design: localStorage behavior remains active.
    });
}

/**
 * Persist UI preference for the authenticated user.
 *
 * @param {string} key
 * @param {unknown} value
 * @param {{ debounceMs?: number }} [options]
 */
export function persistUiPreference(key, value, options = {}) {
    if (typeof key !== 'string' || key.trim() === '') {
        return;
    }

    const debounceMs = Number(options.debounceMs ?? 0);

    if (!Number.isFinite(debounceMs) || debounceMs <= 0) {
        sendPreferenceUpdate(key, value);
        return;
    }

    const previousTimer = debounceTimers.get(key);
    if (previousTimer) {
        window.clearTimeout(previousTimer);
    }

    const timeoutId = window.setTimeout(() => {
        debounceTimers.delete(key);
        sendPreferenceUpdate(key, value);
    }, debounceMs);

    debounceTimers.set(key, timeoutId);
}

/**
 * @returns {{
 *   theme?: 'light'|'dark',
 *   density?: 'normal'|'compact',
 *   sidebarCollapsed?: boolean,
 *   columns?: Record<string, string[]>
 * }}
 */
export function getBootstrapUiPreferences() {
    return getBootstrapPrefs();
}
