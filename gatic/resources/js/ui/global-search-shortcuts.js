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

function findGlobalSearchInput() {
    const element = document.querySelector('[data-global-search="true"]');
    return element instanceof HTMLInputElement ? element : null;
}

export function registerGlobalSearchShortcuts() {
    document.addEventListener(
        'keydown',
        (event) => {
            if (event.defaultPrevented) {
                return;
            }

            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (isEditableTarget(event.target)) {
                return;
            }

            const input = findGlobalSearchInput();
            if (!input) {
                return;
            }

            if (event.key === '/') {
                event.preventDefault();
                input.focus();
                input.select?.();
                return;
            }

            if (event.key === 'Escape' && document.activeElement === input) {
                if (input.value) {
                    input.value = '';
                    return;
                }

                input.blur();
            }
        },
        { capture: true }
    );
}

