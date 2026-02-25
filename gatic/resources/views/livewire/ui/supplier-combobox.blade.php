<div
    class="position-relative"
    x-data="{
        suppressOpenOnFocus: false,
        highlightedIndex: -1,
        activeDescendantId: '',
        open: $wire.entangle('showDropdown'),
        getItems() {
            const listbox = this.$refs.listbox;
            if (!listbox) return [];
            return Array.from(listbox.querySelectorAll('[role=option]'));
        },
        selectHighlighted() {
            const items = this.getItems();
            if (items.length === 0) return;

            if (this.highlightedIndex === -1) {
                this.highlightedIndex = 0;
            }

            const active = items[this.highlightedIndex];
            this.activeDescendantId = active?.id ?? '';
            active?.click();
        },
        moveHighlight(direction) {
            this.open = true;
            const items = this.getItems();
            if (items.length === 0) return;

            const lastIndex = items.length - 1;

            if (this.highlightedIndex === -1) {
                this.highlightedIndex = direction === 'down' ? 0 : lastIndex;
            } else if (direction === 'down') {
                this.highlightedIndex = this.highlightedIndex < lastIndex ? this.highlightedIndex + 1 : 0;
            } else {
                this.highlightedIndex = this.highlightedIndex > 0 ? this.highlightedIndex - 1 : lastIndex;
            }

            const active = items[this.highlightedIndex];
            this.activeDescendantId = active?.id ?? '';
            active?.scrollIntoView({ block: 'nearest' });
        }
    }"
    x-effect="if (!open) { activeDescendantId = ''; highlightedIndex = -1 }"
    x-on:keydown.escape.stop.prevent="
        open = false;
        activeDescendantId = '';
        highlightedIndex = -1;

        // Keep focus on the input so the user can continue typing after closing the dropdown.
        suppressOpenOnFocus = true;
        $nextTick(() => {
            $refs.input?.focus({ preventScroll: true });
            suppressOpenOnFocus = false;
        });
    "
    x-on:click.away="open = false; activeDescendantId = ''; highlightedIndex = -1"
    x-on:supplier-combobox:focus-input.window="
        if ($event.detail?.inputId === '{{ $inputId }}') {
            $refs.input?.focus();
            open = false;
            activeDescendantId = '';
            highlightedIndex = -1;
        }
    "
>
    @if ($supplierId)
        <div class="d-flex align-items-center gap-2 p-2 bg-info bg-opacity-10 border border-info rounded">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <span class="badge bg-info text-dark">
                    <i class="bi bi-truck me-1"></i>Proveedor
                </span>
                <span class="fw-medium">{{ $supplierLabel }}</span>
            </div>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                wire:click="clearSelection"
                aria-label="Cambiar proveedor"
                title="Cambiar proveedor"
                style="min-width: 44px; min-height: 44px;"
            >
                <i class="bi bi-arrow-repeat"></i>
                <span class="visually-hidden">Cambiar</span>
            </button>
        </div>
    @else
        <div class="position-relative">
            <input
                x-ref="input"
                id="{{ $inputId }}"
                type="text"
                class="form-control"
                placeholder="Buscar proveedor..."
                wire:model.live.debounce.300ms="search"
                x-on:keydown.down.prevent="moveHighlight('down')"
                x-on:keydown.up.prevent="moveHighlight('up')"
                x-on:keydown.enter.prevent="selectHighlighted()"
                x-on:focus="if (!suppressOpenOnFocus) { open = true; highlightedIndex = -1; activeDescendantId = '' }"
                role="combobox"
                aria-haspopup="listbox"
                :aria-expanded="open ? 'true' : 'false'"
                aria-controls="{{ $listboxId }}"
                aria-autocomplete="list"
                :aria-activedescendant="activeDescendantId || undefined"
                autocomplete="off"
            />

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="position-absolute w-100 mt-1 gatic-surface-popover border rounded shadow-sm z-3"
                style="max-height: 300px; overflow-y: auto;"
                x-ref="listbox"
                role="listbox"
                id="{{ $listboxId }}"
                aria-label="Lista de proveedores"
            >
                @if (is_string($errorId) && $errorId !== '')
                    <div class="p-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle text-danger mt-1"></i>
                            <div>
                                <div class="fw-semibold">Ocurrió un error inesperado.</div>
                                <div class="small text-muted">
                                    ID: <code class="ms-1">{{ $errorId }}</code>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" wire:click="retrySearch">
                                    Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($showMinCharsMessage)
                    <div class="p-3 text-muted small">Escribe al menos 2 caracteres.</div>
                @elseif ($hasSoftDeletedExactMatch)
                    <div class="p-2">
                        <div class="px-2 py-1 small text-muted">Existe en Papelera.</div>
                        <a
                            href="{{ $trashUrl }}"
                            class="btn btn-sm btn-outline-secondary w-100 text-start"
                            :class="{ 'active': activeDescendantId === $el.id }"
                            role="option"
                            id="{{ $trashOptionId }}"
                            :aria-selected="activeDescendantId === $el.id ? 'true' : 'false'"
                            x-on:mouseenter="
                                const items = $refs.listbox?.querySelectorAll('[role=option]');
                                highlightedIndex = items ? Array.from(items).indexOf($el) : -1;
                                activeDescendantId = $el.id;
                            "
                        >
                            <i class="bi bi-trash3 me-1"></i>Ir a Papelera
                        </a>
                    </div>
                @elseif ($showNoResults)
                    <div class="p-2">
                        <div class="px-2 py-1 small text-muted">Sin resultados.</div>
                        @if ($canCreate)
                            <button
                                type="button"
                                class="btn btn-sm btn-primary w-100 text-start"
                                :class="{ 'active': activeDescendantId === $el.id }"
                                role="option"
                                id="{{ $createOptionId }}"
                                :aria-selected="activeDescendantId === $el.id ? 'true' : 'false'"
                                wire:click="openCreateSupplierModal"
                                wire:loading.attr="disabled"
                                wire:target="openCreateSupplierModal"
                                x-on:mouseenter="
                                    const items = $refs.listbox?.querySelectorAll('[role=option]');
                                    highlightedIndex = items ? Array.from(items).indexOf($el) : -1;
                                    activeDescendantId = $el.id;
                                "
                            >
                                <span wire:loading.remove wire:target="openCreateSupplierModal">
                                    <i class="bi bi-plus-circle me-1"></i>Crear “{{ $search }}”
                                </span>
                                <span wire:loading.inline wire:target="openCreateSupplierModal">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Abriendo...
                                </span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($suppliers as $supplier)
                            <button
                                type="button"
                                class="list-group-item list-group-item-action"
                                :class="{ 'active': activeDescendantId === $el.id }"
                                role="option"
                                id="{{ $optionIdPrefix }}{{ $supplier->id }}"
                                :aria-selected="activeDescendantId === $el.id ? 'true' : 'false'"
                                wire:click="selectSupplier({{ $supplier->id }})"
                                x-on:mouseenter="
                                    const items = $refs.listbox?.querySelectorAll('[role=option]');
                                    highlightedIndex = items ? Array.from(items).indexOf($el) : -1;
                                    activeDescendantId = $el.id;
                                "
                            >
                                <span class="fw-medium">{{ $supplier->name }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($showCreateModal)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0,0,0,0.5);"
            id="{{ $createModalId }}"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $createModalTitleId }}"
            x-on:click.self="$wire.closeCreateSupplierModal()"
            x-on:keydown.escape.stop.prevent="$wire.closeCreateSupplierModal()"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $createModalTitleId }}">Crear proveedor</h5>
                        <button type="button" class="btn-close" wire:click="closeCreateSupplierModal" aria-label="Cerrar"></button>
                    </div>

                    <form wire:submit="createSupplier">
                        <div class="modal-body">
                            @if (is_string($createErrorId) && $createErrorId !== '')
                                <x-ui.error-alert-with-id :error-id="$createErrorId" class="mb-3" />
                            @endif

                            @if (is_string($createTrashUrl) && $createTrashUrl !== '')
                                <div class="alert alert-warning mb-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi bi-trash3 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">Existe en Papelera</div>
                                            <div class="small text-muted">
                                                Restaura el proveedor desde la Papelera para poder seleccionarlo.
                                            </div>
                                            <a href="{{ $createTrashUrl }}" class="btn btn-sm btn-outline-secondary mt-2">
                                                Ir a Papelera
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="{{ $createNameInputId }}" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="{{ $createNameInputId }}"
                                    type="text"
                                    class="form-control @error('createName') is-invalid @enderror"
                                    wire:model="createName"
                                    maxlength="255"
                                />
                                @error('createName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="{{ $createContactInputId }}" class="form-label">Contacto</label>
                                <input
                                    id="{{ $createContactInputId }}"
                                    type="text"
                                    class="form-control @error('createContact') is-invalid @enderror"
                                    wire:model="createContact"
                                    maxlength="255"
                                />
                                @error('createContact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="{{ $createNotesInputId }}" class="form-label">Notas</label>
                                <textarea
                                    id="{{ $createNotesInputId }}"
                                    class="form-control @error('createNotes') is-invalid @enderror"
                                    wire:model="createNotes"
                                    rows="3"
                                    maxlength="1000"
                                ></textarea>
                                @error('createNotes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                wire:click="closeCreateSupplierModal"
                                wire:loading.attr="disabled"
                                wire:target="createSupplier"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="createSupplier"
                            >
                                <span wire:loading.remove wire:target="createSupplier">Guardar</span>
                                <span wire:loading.inline wire:target="createSupplier">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
