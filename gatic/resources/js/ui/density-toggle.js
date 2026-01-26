/**
 * Density Toggle
 *
 * Handles switching between normal and compact density modes.
 * State is persisted in localStorage.
 */

const STORAGE_KEY = 'gatic-density-mode';
const COMPACT_CLASS = 'app-compact';

/**
 * Get current density mode from localStorage
 * @returns {'normal' | 'compact'}
 */
function getStoredMode() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'compact' ? 'compact' : 'normal';
    } catch {
        return 'normal';
    }
}

/**
 * Save density mode to localStorage
 * @param {'normal' | 'compact'} mode
 */
function setStoredMode(mode) {
    try {
        localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        // Ignore storage errors
    }
}

/**
 * Apply density mode to DOM
 * @param {'normal' | 'compact'} mode
 */
function applyMode(mode) {
    const isCompact = mode === 'compact';

    document.body.classList.toggle(COMPACT_CLASS, isCompact);

    // Update toggle buttons
    const toggleBtns = document.querySelectorAll('[data-density-toggle]');
    toggleBtns.forEach((btn) => {
        const icon = btn.querySelector('i');
        if (icon) {
            // Toggle icon between normal (bi-arrows-expand) and compact (bi-arrows-collapse)
            icon.classList.toggle('bi-arrows-angle-expand', !isCompact);
            icon.classList.toggle('bi-arrows-angle-contract', isCompact);
        }

        const text = btn.querySelector('.density-text');
        if (text) {
            text.textContent = isCompact ? 'Normal' : 'Compacto';
        }

        btn.setAttribute('aria-pressed', isCompact);
        btn.setAttribute('title', isCompact ? 'Cambiar a modo normal' : 'Cambiar a modo compacto');
    });
}

/**
 * Toggle between normal and compact modes
 */
function toggleDensity() {
    const currentMode = getStoredMode();
    const newMode = currentMode === 'compact' ? 'normal' : 'compact';

    applyMode(newMode);
    setStoredMode(newMode);
}

/**
 * Register density toggle functionality
 */
export function registerDensityToggle() {
    // Apply stored mode on page load
    const initialMode = getStoredMode();
    applyMode(initialMode);

    // Handle toggle button click
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-density-toggle]');
        if (btn) {
            event.preventDefault();
            toggleDensity();
        }
    });

    // Listen for global toggle event
    window.addEventListener('gatic:toggle-density', () => {
        toggleDensity();
    });
}
