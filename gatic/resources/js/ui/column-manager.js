import { persistUiPreference } from './user-ui-preferences';

const STORAGE_PREFIX = 'gatic:columns:';

function getStorageKey(tableKey) {
    return `${STORAGE_PREFIX}${tableKey}`;
}

function readHiddenSet(tableKey) {
    try {
        const raw = localStorage.getItem(getStorageKey(tableKey));
        const parsed = JSON.parse(raw ?? '[]');
        if (!Array.isArray(parsed)) return new Set();
        return new Set(parsed.filter((v) => typeof v === 'string'));
    } catch {
        return new Set();
    }
}

function writeHiddenSet(tableKey, hiddenSet) {
    try {
        localStorage.setItem(getStorageKey(tableKey), JSON.stringify(Array.from(hiddenSet)));
    } catch {
        // ignore
    }
}

function getTableEl(tableKey) {
    const el = document.querySelector(`[data-column-table="${tableKey}"]`);
    return el instanceof HTMLTableElement ? el : null;
}

function getColumnDefs(tableEl) {
    const ths = Array.from(tableEl.querySelectorAll('thead tr th'));

    return ths
        .map((th, index) => {
            if (!(th instanceof HTMLTableCellElement)) return null;

            const key = th.dataset.columnKey;
            if (!key) return null;

            const required = th.dataset.columnRequired === 'true';
            const label = (th.dataset.columnLabel ?? th.textContent ?? '').trim() || key;

            return { key, label, required, index };
        })
        .filter((col) => col !== null);
}

function applyVisibility(tableEl, columns, hiddenSet) {
    const ths = Array.from(tableEl.querySelectorAll('thead tr th'));
    const rows = Array.from(tableEl.querySelectorAll('tbody tr'));

    const requiredKeys = new Set(columns.filter((c) => c.required).map((c) => c.key));
    let hiddenChanged = false;

    hiddenSet.forEach((key) => {
        if (requiredKeys.has(key)) {
            hiddenSet.delete(key);
            hiddenChanged = true;
        }
    });

    columns.forEach((col) => {
        const shouldHide = hiddenSet.has(col.key) && !col.required;
        const th = ths[col.index];
        if (th) th.classList.toggle('d-none', shouldHide);

        rows.forEach((row) => {
            const cell = row.children[col.index];
            if (cell instanceof HTMLElement) {
                cell.classList.toggle('d-none', shouldHide);
            }
        });
    });

    // Fix empty-state colspans when hiding columns.
    const totalColumns = ths.length;
    const visibleColumns = columns.filter((c) => !hiddenSet.has(c.key) || c.required).length;
    tableEl.querySelectorAll('tbody td[colspan]').forEach((td) => {
        if (!(td instanceof HTMLTableCellElement)) return;
        const rawOriginal = td.dataset.columnManagerOriginalColspan ?? td.getAttribute('colspan');
        const originalColspan = Number(rawOriginal);
        if (!Number.isFinite(originalColspan)) return;

        if (!td.dataset.columnManagerOriginalColspan) {
            td.dataset.columnManagerOriginalColspan = String(originalColspan);
        }

        if (originalColspan === totalColumns) {
            td.setAttribute('colspan', String(Math.max(1, visibleColumns)));
        }
    });

    return hiddenChanged;
}

function buildMenu(managerEl, tableKey, columns, hiddenSet) {
    const listEl = managerEl.querySelector('[data-column-manager-list]');
    if (!(listEl instanceof HTMLElement)) return;

    listEl.innerHTML = '';

    columns.forEach((col) => {
        const id = `colmgr-${tableKey}-${col.key}`;

        const wrapper = document.createElement('div');
        wrapper.className = 'form-check';

        const input = document.createElement('input');
        input.className = 'form-check-input';
        input.type = 'checkbox';
        input.id = id;
        input.dataset.columnKey = col.key;
        input.checked = !hiddenSet.has(col.key) || col.required;
        input.disabled = col.required;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = id;
        label.textContent = col.label;

        wrapper.appendChild(input);
        wrapper.appendChild(label);
        listEl.appendChild(wrapper);
    });
}

function initManager(managerEl) {
    if (!(managerEl instanceof HTMLElement)) return;
    if (managerEl.dataset.columnManagerInitialized === 'true') return;

    const tableKey = managerEl.dataset.columnManager;
    if (!tableKey) return;

    const tableEl = getTableEl(tableKey);
    if (!tableEl) return;

    const columns = getColumnDefs(tableEl);
    if (columns.length === 0) return;

    const hiddenSet = readHiddenSet(tableKey);
    applyVisibility(tableEl, columns, hiddenSet);
    buildMenu(managerEl, tableKey, columns, hiddenSet);

    managerEl.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) return;
        if (target.type !== 'checkbox') return;
        if (!target.dataset.columnKey) return;

        const key = target.dataset.columnKey;
        const freshHidden = readHiddenSet(tableKey);

        if (target.checked) {
            freshHidden.delete(key);
        } else {
            freshHidden.add(key);
        }

        writeHiddenSet(tableKey, freshHidden);
        persistUiPreference(`ui.columns.${tableKey}`, Array.from(freshHidden), { debounceMs: 500 });

        const freshColumns = getColumnDefs(tableEl);
        applyVisibility(tableEl, freshColumns, freshHidden);
        buildMenu(managerEl, tableKey, freshColumns, freshHidden);
    });

    managerEl.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        const resetBtn = target.closest('[data-column-manager-reset]');
        if (!resetBtn) return;

        event.preventDefault();

        const empty = new Set();
        writeHiddenSet(tableKey, empty);
        persistUiPreference(`ui.columns.${tableKey}`, [], { debounceMs: 500 });

        const freshColumns = getColumnDefs(tableEl);
        applyVisibility(tableEl, freshColumns, empty);
        buildMenu(managerEl, tableKey, freshColumns, empty);
    });

    managerEl.dataset.columnManagerInitialized = 'true';
}

function applyAllTables() {
    const tables = Array.from(document.querySelectorAll('[data-column-table]')).filter(
        (el) => el instanceof HTMLTableElement
    );

    tables.forEach((tableEl) => {
        const tableKey = tableEl.dataset.columnTable;
        if (!tableKey) return;

        const columns = getColumnDefs(tableEl);
        if (columns.length === 0) return;

        const hiddenSet = readHiddenSet(tableKey);
        const hiddenChanged = applyVisibility(tableEl, columns, hiddenSet);
        if (hiddenChanged) {
            writeHiddenSet(tableKey, hiddenSet);
        }
    });
}

function initAllManagers() {
    document.querySelectorAll('[data-column-manager]').forEach((managerEl) => {
        initManager(managerEl);
    });
}

let registered = false;

export function registerColumnManager() {
    if (registered) return;
    registered = true;

    const init = () => {
        applyAllTables();
        initAllManagers();
    };

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }

    // Livewire updates can replace table rows; re-apply visibility after morph.
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire?.hook) return;

        window.Livewire.hook('morph.updated', ({ el }) => {
            if (!(el instanceof HTMLElement)) {
                init();
                return;
            }

            // Fast path: if the updated element is inside a managed table, only re-apply that table.
            const closestTable = el.closest('[data-column-table]');
            if (closestTable instanceof HTMLTableElement) {
                const tableKey = closestTable.dataset.columnTable;
                if (tableKey) {
                    const columns = getColumnDefs(closestTable);
                    const hiddenSet = readHiddenSet(tableKey);
                    const hiddenChanged = applyVisibility(closestTable, columns, hiddenSet);
                    if (hiddenChanged) {
                        writeHiddenSet(tableKey, hiddenSet);
                    }
                }

                // Managers likely didn't change in this morph.
                return;
            }

            // If a new manager/table was introduced, initialize everything once.
            if (el.querySelector('[data-column-manager]') || el.querySelector('[data-column-table]')) {
                init();
            }
        });
    });
}
