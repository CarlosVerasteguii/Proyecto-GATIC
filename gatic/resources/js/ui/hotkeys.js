/**
 * Advanced Keyboard Shortcuts for GATIC
 *
 * Shortcuts:
 * - Ctrl/Cmd+K: Open command palette / focus search
 * - ?: Show keyboard shortcuts help modal
 * - j/k: Navigate table rows (down/up)
 * - Enter: Open selected row detail
 * - Ctrl/Cmd+Enter: Submit form
 * - [: Toggle sidebar
 * - Escape: Close modals/drawers, blur inputs
 */

const HOTKEYS_MODAL_ID = 'hotkeys-help-modal';

/**
 * Check if target is an editable element
 */
function isEditableTarget(target) {
    if (!(target instanceof Element)) {
        return false;
    }

    const tagName = target.tagName.toLowerCase();
    if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
        return true;
    }

    return target.isContentEditable;
}

/**
 * Check if any modal is open
 */
function hasOpenModal() {
    return document.querySelector('.modal.show') !== null;
}

/**
 * Check if any drawer is open
 */
function hasOpenDrawer() {
    return document.querySelector('.drawer.show') !== null;
}

/**
 * Get Bootstrap modal instance for hotkeys help
 */
function getHotkeysModal() {
    const modalEl = document.getElementById(HOTKEYS_MODAL_ID);
    if (!modalEl) return null;

    // Get or create Bootstrap modal instance
    if (window.bootstrap?.Modal) {
        return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    }
    return null;
}

/**
 * Focus global search input
 */
function focusGlobalSearch() {
    const input = document.querySelector('[data-global-search="true"]');
    if (input instanceof HTMLInputElement) {
        input.focus();
        input.select?.();
        return true;
    }
    return false;
}

/**
 * Open the command palette (if present)
 */
function openCommandPalette() {
    const paletteEl = document.querySelector('[data-command-palette="true"]');
    if (!paletteEl) return false;

    window.dispatchEvent(new CustomEvent('gatic:open-command-palette'));
    return true;
}

/**
 * Navigate table rows with j/k keys
 */
function navigateTableRows(direction) {
    const tables = document.querySelectorAll('table.table tbody');
    if (tables.length === 0) return false;

    // Find the first visible table
    const table = Array.from(tables).find((t) => t.offsetParent !== null);
    if (!table) return false;

    const rows = Array.from(table.querySelectorAll('tr:not(.empty-state-row)'));
    if (rows.length === 0) return false;

    // Find currently selected row
    const selectedIndex = rows.findIndex((row) => row.classList.contains('table-active'));
    let newIndex;

    if (direction === 'down') {
        newIndex = selectedIndex === -1 ? 0 : Math.min(selectedIndex + 1, rows.length - 1);
    } else {
        newIndex = selectedIndex === -1 ? rows.length - 1 : Math.max(selectedIndex - 1, 0);
    }

    // Update selection
    rows.forEach((row, i) => {
        row.classList.toggle('table-active', i === newIndex);
    });

    // Scroll into view
    rows[newIndex]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    return true;
}

/**
 * Open detail for selected table row
 */
function openSelectedRow() {
    const selectedRow = document.querySelector('table.table tbody tr.table-active');
    if (!selectedRow) return false;

    // Find the first link in the row (usually "Ver" or the name link)
    const link = selectedRow.querySelector('a[href]');
    if (link) {
        link.click();
        return true;
    }

    return false;
}

/**
 * Submit the closest form with Ctrl/Cmd+Enter
 */
function submitCurrentForm() {
    const activeElement = document.activeElement;
    if (!activeElement) return false;

    // Find the closest form
    const form = activeElement.closest('form');
    if (!form) return false;

    // Find submit button or trigger form submission
    const submitBtn = form.querySelector(
        'button[type="submit"], input[type="submit"], button[wire\\:click*="save"], button[wire\\:click*="submit"]'
    );

    if (submitBtn) {
        submitBtn.click();
        return true;
    }

    // Try native form submission
    form.requestSubmit?.();
    return true;
}

/**
 * Toggle sidebar collapsed state
 */
function toggleSidebar() {
    // Dispatch custom event that sidebar-toggle.js listens for
    window.dispatchEvent(new CustomEvent('gatic:toggle-sidebar'));
    return true;
}

/**
 * Show keyboard shortcuts help modal
 */
function showHotkeysHelp() {
    const modal = getHotkeysModal();
    if (modal) {
        modal.show();
        return true;
    }
    return false;
}

/**
 * Close any open modal or drawer
 */
function closeOverlays() {
    // Close Bootstrap modals
    const openModal = document.querySelector('.modal.show');
    if (openModal && window.bootstrap?.Modal) {
        const modalInstance = window.bootstrap.Modal.getInstance(openModal);
        modalInstance?.hide();
        return true;
    }

    // Close drawers
    const openDrawer = document.querySelector('.drawer.show');
    if (openDrawer) {
        openDrawer.classList.remove('show');
        document.body.classList.remove('drawer-open');
        return true;
    }

    return false;
}

/**
 * Main keydown handler
 */
function handleKeydown(event) {
    // Ignore if event was already handled
    if (event.defaultPrevented) return;

    const { key, ctrlKey, metaKey, shiftKey } = event;
    const modKey = ctrlKey || metaKey;
    const target = event.target;
    const isEditable = isEditableTarget(target);

    // ===== Global shortcuts (work everywhere) =====

    // Ctrl/Cmd+K - Focus search / command palette
    if (modKey && key.toLowerCase() === 'k') {
        event.preventDefault();
        if (!openCommandPalette()) {
            focusGlobalSearch();
        }
        return;
    }

    // Ctrl/Cmd+Enter - Submit form (only in editable contexts)
    if (modKey && key === 'Enter' && isEditable) {
        event.preventDefault();
        submitCurrentForm();
        return;
    }

    // Escape - Close overlays or blur input
    if (key === 'Escape') {
        if (hasOpenModal() || hasOpenDrawer()) {
            event.preventDefault();
            closeOverlays();
            return;
        }

        if (isEditable && target instanceof HTMLElement) {
            target.blur();
            return;
        }
    }

    // ===== Non-editable context shortcuts =====
    if (isEditable) return;

    // ? - Show keyboard shortcuts help
    if (key === '?' || (shiftKey && key === '/')) {
        event.preventDefault();
        showHotkeysHelp();
        return;
    }

    // [ - Toggle sidebar
    if (key === '[') {
        event.preventDefault();
        toggleSidebar();
        return;
    }

    // j/k or ArrowDown/ArrowUp - Navigate table rows
    if (key === 'j' || key === 'ArrowDown') {
        if (!hasOpenModal()) {
            event.preventDefault();
            navigateTableRows('down');
            return;
        }
    }

    if (key === 'k' || key === 'ArrowUp') {
        if (!hasOpenModal()) {
            event.preventDefault();
            navigateTableRows('up');
            return;
        }
    }

    // Enter - Open selected row
    if (key === 'Enter') {
        if (!hasOpenModal() && openSelectedRow()) {
            event.preventDefault();
            return;
        }
    }
}

/**
 * Register all hotkey handlers
 */
export function registerHotkeys() {
    document.addEventListener('keydown', handleKeydown, { capture: true });

    // Log that hotkeys are registered (dev mode only)
    if (import.meta.env?.DEV) {
        console.log('[GATIC] Hotkeys registered. Press ? for help.');
    }
}
