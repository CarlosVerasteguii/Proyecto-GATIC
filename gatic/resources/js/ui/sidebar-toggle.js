/**
 * Sidebar Toggle
 *
 * Handles collapsing/expanding the sidebar on desktop.
 * State is persisted in localStorage.
 */
import { getBootstrapUiPreferences, persistUiPreference } from './user-ui-preferences';

const STORAGE_KEY = 'gatic-sidebar-collapsed';
const COLLAPSED_CLASS = 'sidebar-collapsed';

/**
 * Get current collapsed state from localStorage
 */
function getStoredState() {
    const bootstrap = getBootstrapUiPreferences();
    if (typeof bootstrap?.sidebarCollapsed === 'boolean') {
        return bootstrap.sidebarCollapsed;
    }

    try {
        return localStorage.getItem(STORAGE_KEY) === 'true';
    } catch {
        return false;
    }
}

/**
 * Save collapsed state to localStorage
 */
function setStoredState(collapsed) {
    try {
        localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
    } catch {
        // Ignore storage errors
    }
}

/**
 * Apply collapsed state to DOM
 */
function applyState(collapsed) {
    const appShell = document.querySelector('.app-shell');
    const toggleBtn = document.querySelector('[data-sidebar-toggle]');

    if (appShell) {
        appShell.classList.toggle(COLLAPSED_CLASS, collapsed);
    }

    if (toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-chevron-left', !collapsed);
            icon.classList.toggle('bi-chevron-right', collapsed);
        }
        toggleBtn.setAttribute('aria-expanded', !collapsed);
        toggleBtn.setAttribute('title', collapsed ? 'Expandir sidebar' : 'Colapsar sidebar');
    }
}

/**
 * Toggle sidebar state
 */
function toggleSidebar() {
    const appShell = document.querySelector('.app-shell');
    if (!appShell) return;

    const isCurrentlyCollapsed = appShell.classList.contains(COLLAPSED_CLASS);
    const newState = !isCurrentlyCollapsed;

    applyState(newState);
    setStoredState(newState);
    persistUiPreference('ui.sidebar_collapsed', newState);
}

/**
 * Register sidebar toggle functionality
 */
export function registerSidebarToggle() {
    // Apply stored state on page load
    const initialState = getStoredState();
    applyState(initialState);

    // Handle toggle button click
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-sidebar-toggle]');
        if (btn) {
            event.preventDefault();
            toggleSidebar();
        }
    });

    // Listen for global toggle event (from hotkeys.js)
    window.addEventListener('gatic:toggle-sidebar', () => {
        toggleSidebar();
    });
}
