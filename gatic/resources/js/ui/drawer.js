/**
 * Drawer Component
 *
 * Handles opening, closing, and keyboard navigation for drawer panels.
 */

/**
 * Open a drawer by ID
 */
function openDrawer(id) {
    const drawer = document.querySelector(`[data-drawer="${id}"]`);
    if (!drawer) return false;

    drawer.classList.add('show');
    document.body.classList.add('drawer-open');

    // Focus first focusable element in drawer
    const focusable = drawer.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    focusable?.focus();

    return true;
}

/**
 * Close a drawer by ID
 */
function closeDrawer(id) {
    const drawer = document.querySelector(`[data-drawer="${id}"]`);
    if (!drawer) return false;

    drawer.classList.remove('show');

    // Only remove body class if no other drawers are open
    const hasOpenDrawers = document.querySelector('.drawer.show');
    if (!hasOpenDrawers) {
        document.body.classList.remove('drawer-open');
    }

    return true;
}

/**
 * Close all open drawers
 */
function closeAllDrawers() {
    const openDrawers = document.querySelectorAll('.drawer.show');
    openDrawers.forEach((drawer) => {
        drawer.classList.remove('show');
    });
    document.body.classList.remove('drawer-open');
}

/**
 * Register drawer functionality
 */
export function registerDrawer() {
    // Handle toggle buttons
    document.addEventListener('click', (event) => {
        // Open button
        const toggleBtn = event.target.closest('[data-drawer-toggle]');
        if (toggleBtn) {
            event.preventDefault();
            const id = toggleBtn.getAttribute('data-drawer-toggle');
            openDrawer(id);
            return;
        }

        // Close button or backdrop
        const closeBtn = event.target.closest('[data-drawer-close]');
        if (closeBtn) {
            event.preventDefault();
            const id = closeBtn.getAttribute('data-drawer-close');
            closeDrawer(id);
            return;
        }
    });

    // Handle Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openDrawer = document.querySelector('.drawer.show');
            if (openDrawer) {
                const closeOnEsc = openDrawer.getAttribute('data-close-on-esc') !== 'false';
                if (closeOnEsc) {
                    event.preventDefault();
                    closeAllDrawers();
                }
            }
        }
    });

    // Listen for Livewire events
    if (window.Livewire) {
        window.Livewire.on('drawer-open', ({ id }) => {
            openDrawer(id);
        });

        window.Livewire.on('drawer-close', ({ id }) => {
            closeDrawer(id);
        });
    }

    // Also listen for custom events (for non-Livewire usage)
    window.addEventListener('gatic:drawer-open', (event) => {
        const { id } = event.detail || {};
        if (id) openDrawer(id);
    });

    window.addEventListener('gatic:drawer-close', (event) => {
        const { id } = event.detail || {};
        if (id) closeDrawer(id);
    });
}

// Export functions for programmatic use
export { openDrawer, closeDrawer, closeAllDrawers };
