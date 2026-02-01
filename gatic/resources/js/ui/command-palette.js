const MODAL_ID = 'gaticCommandPaletteModal';

function getModalEl() {
    const el = document.getElementById(MODAL_ID);
    return el instanceof HTMLElement ? el : null;
}

function getInputEl(modalEl) {
    const el = modalEl.querySelector('[data-command-palette-input]');
    return el instanceof HTMLInputElement ? el : null;
}

function getResultsEl(modalEl) {
    const el = modalEl.querySelector('[data-command-palette-results]');
    return el instanceof HTMLElement ? el : null;
}

function getItems(modalEl) {
    return Array.from(modalEl.querySelectorAll('[data-command-palette-item]')).filter(
        (el) => el instanceof HTMLElement
    );
}

function isModalOpen(modalEl) {
    return modalEl.classList.contains('show');
}

function setActiveItem(modalEl, index) {
    const items = getItems(modalEl);
    if (items.length === 0) return 0;

    const nextIndex = Math.max(0, Math.min(index, items.length - 1));

    items.forEach((item, i) => {
        item.classList.toggle('active', i === nextIndex);
    });

    items[nextIndex]?.scrollIntoView({ block: 'nearest' });

    return nextIndex;
}

function openPalette(modalEl) {
    if (!window.bootstrap?.Modal) return false;

    const instance = window.bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: true,
        keyboard: true,
        focus: true,
    });

    instance.show();
    return true;
}

let registered = false;

export function registerCommandPalette() {
    if (registered) return;
    registered = true;

    const modalEl = getModalEl();
    if (!modalEl) return;

    const inputEl = getInputEl(modalEl);
    const resultsEl = getResultsEl(modalEl);
    let activeIndex = 0;

    // Open from global hotkey
    window.addEventListener('gatic:open-command-palette', () => {
        if (openPalette(modalEl)) {
            // If already open, just focus the input.
            if (isModalOpen(modalEl)) {
                inputEl?.focus();
                inputEl?.select?.();
            }
        }
    });

    // Focus input and reset active selection when shown
    modalEl.addEventListener('shown.bs.modal', () => {
        inputEl?.focus();
        inputEl?.select?.();
        activeIndex = setActiveItem(modalEl, 0);
    });

    // Clear query when closed
    modalEl.addEventListener('hidden.bs.modal', () => {
        activeIndex = 0;

        if (window.Livewire?.dispatch) {
            window.Livewire.dispatch('ui:command-palette-reset');
        }
    });

    // Hide modal after clicking an item (navigation)
    modalEl.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        const item = target.closest('[data-command-palette-item]');
        if (!item) return;

        if (!window.bootstrap?.Modal) return;
        const instance = window.bootstrap.Modal.getInstance(modalEl);
        instance?.hide();
    });

    // Keyboard navigation
    modalEl.addEventListener(
        'keydown',
        (event) => {
            if (!isModalOpen(modalEl)) return;

            const { key } = event;

            if (key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = setActiveItem(modalEl, activeIndex + 1);
                return;
            }

            if (key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = setActiveItem(modalEl, activeIndex - 1);
                return;
            }

            if (key === 'Enter') {
                const items = getItems(modalEl);
                const activeItem = items[activeIndex];
                if (!activeItem) return;

                // If focus is in the input, Enter means "execute"
                if (document.activeElement === inputEl) {
                    event.preventDefault();
                    activeItem.click();
                }
            }
        },
        { capture: true }
    );

    // If Livewire re-renders the results, reset active selection safely.
    if (resultsEl) {
        const observer = new MutationObserver(() => {
            if (!isModalOpen(modalEl)) return;
            activeIndex = setActiveItem(modalEl, 0);
        });

        observer.observe(resultsEl, { childList: true, subtree: true });
    }
}

