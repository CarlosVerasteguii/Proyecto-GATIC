const DIALOG_SELECTOR = '[data-manual-dialog]';

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

const activeDialogs = new Map();
const dialogStack = [];

function isVisible(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    if (element.hidden) {
        return false;
    }

    const style = window.getComputedStyle(element);

    return style.display !== 'none' && style.visibility !== 'hidden';
}

function getFocusableElements(dialog) {
    return Array.from(dialog.querySelectorAll(FOCUSABLE_SELECTOR)).filter((element) => isVisible(element));
}

function getTopDialog() {
    return dialogStack.at(-1) ?? null;
}

function getDialogRestoreTarget(dialog, fallback) {
    const restoreId = dialog.dataset.manualDialogRestoreId;
    if (restoreId) {
        const target = document.getElementById(restoreId);
        if (isVisible(target)) {
            return target;
        }
    }

    const restoreSelector = dialog.dataset.manualDialogRestoreSelector;
    if (restoreSelector) {
        const target = document.querySelector(restoreSelector);
        if (isVisible(target)) {
            return target;
        }
    }

    return isVisible(fallback) ? fallback : null;
}

function focusElement(element) {
    if (!(element instanceof HTMLElement) || !isVisible(element)) {
        return false;
    }

    element.focus({ preventScroll: true });

    return document.activeElement === element;
}

function focusInitialElement(dialog) {
    const preferred = dialog.querySelector('[data-manual-dialog-initial-focus]');
    if (focusElement(preferred)) {
        return;
    }

    const firstFocusable = getFocusableElements(dialog)[0] ?? dialog;
    focusElement(firstFocusable);
}

function updateScrollLock() {
    const hasOpenDialogs = dialogStack.length > 0;
    const body = document.body;

    if (!hasOpenDialogs) {
        if (body.dataset.manualDialogOverflow !== undefined) {
            body.style.overflow = body.dataset.manualDialogOverflow;
            delete body.dataset.manualDialogOverflow;
        }

        if (body.dataset.manualDialogPaddingRight !== undefined) {
            body.style.paddingRight = body.dataset.manualDialogPaddingRight;
            delete body.dataset.manualDialogPaddingRight;
        }

        body.classList.remove('modal-open');

        return;
    }

    if (body.dataset.manualDialogOverflow === undefined) {
        body.dataset.manualDialogOverflow = body.style.overflow;
        body.dataset.manualDialogPaddingRight = body.style.paddingRight;

        const scrollbarWidth = Math.max(window.innerWidth - document.documentElement.clientWidth, 0);
        body.style.overflow = 'hidden';

        if (scrollbarWidth > 0) {
            body.style.paddingRight = `${scrollbarWidth}px`;
        }
    }

    body.classList.add('modal-open');
}

function trapTabKey(dialog, event) {
    const focusableElements = getFocusableElements(dialog);

    if (focusableElements.length === 0) {
        event.preventDefault();
        focusElement(dialog);

        return;
    }

    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements.at(-1);
    const activeElement = document.activeElement;

    if (event.shiftKey) {
        if (activeElement === firstFocusable || !dialog.contains(activeElement)) {
            event.preventDefault();
            focusElement(lastFocusable);
        }

        return;
    }

    if (activeElement === lastFocusable || !dialog.contains(activeElement)) {
        event.preventDefault();
        focusElement(firstFocusable);
    }
}

function requestCloseDialog(dialog) {
    const state = activeDialogs.get(dialog);
    if (state?.isClosing) {
        return;
    }

    const closeMethod = dialog.dataset.manualDialogCloseMethod;
    const componentRoot = dialog.closest('[wire\\:id]');
    const componentId = componentRoot?.getAttribute('wire:id');

    if (
        typeof closeMethod === 'string' &&
        closeMethod !== '' &&
        typeof window.Livewire?.find === 'function' &&
        typeof componentId === 'string' &&
        componentId !== ''
    ) {
        const component = window.Livewire.find(componentId);

        if (component && typeof component.call === 'function') {
            state.isClosing = true;
            component.call(closeMethod);

            return;
        }
    }

    const closeButton = dialog.querySelector('[data-manual-dialog-close]');

    if (closeButton instanceof HTMLElement && !closeButton.hasAttribute('disabled')) {
        state.isClosing = true;
        closeButton.click();
    }
}

function activateDialog(dialog) {
    if (!(dialog instanceof HTMLElement) || activeDialogs.has(dialog)) {
        return;
    }

    const fallbackRestoreTarget =
        document.activeElement instanceof HTMLElement ? document.activeElement : null;

    const state = {
        isClosing: false,
        fallbackRestoreTarget,
        onKeydown(event) {
            if (getTopDialog() !== dialog) {
                return;
            }

            if (event.key === 'Tab') {
                trapTabKey(dialog, event);

                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                requestCloseDialog(dialog);
            }
        },
    };

    activeDialogs.set(dialog, state);
    dialogStack.push(dialog);
    updateScrollLock();

    if (!dialog.hasAttribute('tabindex')) {
        dialog.setAttribute('tabindex', '-1');
    }

    dialog.addEventListener('keydown', state.onKeydown);

    requestAnimationFrame(() => {
        if (activeDialogs.has(dialog) && getTopDialog() === dialog) {
            focusInitialElement(dialog);
        }
    });
}

function deactivateDialog(dialog) {
    const state = activeDialogs.get(dialog);
    if (!state) {
        return;
    }

    dialog.removeEventListener('keydown', state.onKeydown);
    activeDialogs.delete(dialog);

    const stackIndex = dialogStack.indexOf(dialog);
    if (stackIndex !== -1) {
        dialogStack.splice(stackIndex, 1);
    }

    updateScrollLock();

    requestAnimationFrame(() => {
        if (getTopDialog() !== null) {
            focusInitialElement(getTopDialog());

            return;
        }

        focusElement(getDialogRestoreTarget(dialog, state.fallbackRestoreTarget));
    });
}

function syncDialogs() {
    const dialogs = Array.from(document.querySelectorAll(DIALOG_SELECTOR));
    const currentDialogs = new Set(dialogs);

    dialogs.forEach((dialog) => activateDialog(dialog));

    Array.from(activeDialogs.keys()).forEach((dialog) => {
        if (!currentDialogs.has(dialog)) {
            deactivateDialog(dialog);
        }
    });
}

export function registerManualDialog() {
    if (window.__gaticManualDialogRegistered) {
        syncDialogs();

        return;
    }

    window.__gaticManualDialogRegistered = true;

    const observer = new MutationObserver(() => syncDialogs());
    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    const handleEscape = (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const topDialog = getTopDialog();
        if (topDialog === null) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        requestCloseDialog(topDialog);
    };

    document.addEventListener('keydown', handleEscape, true);

    document.addEventListener(
        'click',
        (event) => {
            const closeTrigger = event.target instanceof Element
                ? event.target.closest('[data-manual-dialog-close]')
                : null;

            if (!(closeTrigger instanceof HTMLElement)) {
                return;
            }

            const dialog = closeTrigger.closest(DIALOG_SELECTOR);
            if (!(dialog instanceof HTMLElement) || dialog.dataset.manualDialogCloseMethod === undefined) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            requestCloseDialog(dialog);
        },
        true,
    );

    syncDialogs();
}
