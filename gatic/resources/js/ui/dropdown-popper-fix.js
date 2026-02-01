let registered = false;

export function registerDropdownPopperFix() {
    if (registered) return;
    registered = true;

    const RESPONSIVE_TABLE_SELECTOR =
        '.table-responsive, .table-responsive-sm, .table-responsive-md, .table-responsive-lg, .table-responsive-xl, .table-responsive-xxl';

    function getResponsiveTableContainer(target) {
        if (!(target instanceof Element)) return null;
        return target.closest(RESPONSIVE_TABLE_SELECTOR);
    }

    // Fix: dropdown menus inside `.table-responsive*` get clipped because Bootstrap sets overflow-x,
    // which forces overflow-y to become auto. Temporarily allow overflow while the dropdown is open.
    document.addEventListener('show.bs.dropdown', (event) => {
        const container = getResponsiveTableContainer(event.target);
        if (!container) return;

        if (container.dataset.gaticOverflowRestore !== undefined) {
            return;
        }

        container.dataset.gaticOverflowRestore = container.style.overflow || '';
        container.style.overflow = 'visible';
    });

    document.addEventListener('hidden.bs.dropdown', (event) => {
        const container = getResponsiveTableContainer(event.target);
        if (!container) return;

        if (container.dataset.gaticOverflowRestore === undefined) {
            return;
        }

        container.style.overflow = container.dataset.gaticOverflowRestore;
        delete container.dataset.gaticOverflowRestore;
    });
}
