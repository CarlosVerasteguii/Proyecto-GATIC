const STORAGE_KEY = 'gatic:theme';

function getStoredTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'dark' || stored === 'light' ? stored : null;
    } catch {
        return null;
    }
}

function getSystemTheme() {
    try {
        return window.matchMedia?.('(prefers-color-scheme: dark)')?.matches === true ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

function getPreferredTheme() {
    return getStoredTheme() ?? getSystemTheme();
}

function setStoredTheme(theme) {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // ignore
    }
}

function applyTheme(theme) {
    const resolvedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
    updateToggleButtons(resolvedTheme);

    window.dispatchEvent(
        new CustomEvent('gatic:theme-changed', {
            detail: { theme: resolvedTheme },
        })
    );
}

function updateToggleButtons(theme) {
    const isDark = theme === 'dark';
    const buttons = document.querySelectorAll('[data-theme-toggle]');

    buttons.forEach((btn) => {
        if (!(btn instanceof HTMLElement)) return;

        btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');

        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('bi-moon-stars', 'bi-sun');
            icon.classList.add(isDark ? 'bi-sun' : 'bi-moon-stars');
        }

        const label = btn.querySelector('.theme-text');
        if (label) {
            label.textContent = isDark ? 'Claro' : 'Oscuro';
        }

        btn.setAttribute('title', isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
    });
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    setStoredTheme(next);
    applyTheme(next);
}

let registered = false;

export function registerThemeToggle() {
    if (registered) return;
    registered = true;

    // Ensure we apply a deterministic theme even if the inline bootstrapper is skipped.
    applyTheme(getPreferredTheme());

    document.addEventListener('click', (event) => {
        const btn = event.target.closest?.('[data-theme-toggle]');
        if (!btn) return;

        event.preventDefault();
        toggleTheme();
    });
}

