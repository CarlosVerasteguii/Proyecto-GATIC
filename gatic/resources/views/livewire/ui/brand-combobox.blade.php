<div
    class="position-relative"
    x-data="{
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
    x-on:keydown.escape.prevent="open = false; activeDescendantId = ''; highlightedIndex = -1; $refs.input?.blur()"
    x-on:click.away="open = false; activeDescendantId = ''; highlightedIndex = -1"
>
    @if ($brandId)
        <div class="d-flex align-items-center gap-2 p-2 bg-primary bg-opacity-10 border border-primary rounded">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <span class="badge bg-primary">
                    <i class="bi bi-tag me-1"></i>Marca
                </span>
                <span class="fw-medium">{{ $brandLabel }}</span>
            </div>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                wire:click="clearSelection"
                aria-label="Cambiar marca"
                title="Cambiar marca"
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
                placeholder="Buscar marca..."
                wire:model.live.debounce.300ms="search"
                x-on:keydown.down.prevent="moveHighlight('down')"
                x-on:keydown.up.prevent="moveHighlight('up')"
                x-on:keydown.enter.prevent="selectHighlighted()"
                x-on:focus="open = true; highlightedIndex = -1; activeDescendantId = ''"
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
                aria-label="Lista de marcas"
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
                                 wire:click="createFromSearch"
                                 wire:loading.attr="disabled"
                                 wire:target="createFromSearch"
                                 x-on:mouseenter="
                                     const items = $refs.listbox?.querySelectorAll('[role=option]');
                                     highlightedIndex = items ? Array.from(items).indexOf($el) : -1;
                                     activeDescendantId = $el.id;
                                 "
                             >
                                 <span wire:loading.remove wire:target="createFromSearch">
                                     <i class="bi bi-plus-circle me-1"></i>Crear “{{ $search }}”
                                 </span>
                                 <span wire:loading.inline wire:target="createFromSearch">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Creando...
                                </span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="list-group list-group-flush">
                         @foreach ($brands as $brand)
                             <button
                                 type="button"
                                 class="list-group-item list-group-item-action"
                                 :class="{ 'active': activeDescendantId === $el.id }"
                                 role="option"
                                 id="{{ $optionIdPrefix }}{{ $brand->id }}"
                                 :aria-selected="activeDescendantId === $el.id ? 'true' : 'false'"
                                 wire:click="selectBrand({{ $brand->id }})"
                                 x-on:mouseenter="
                                     const items = $refs.listbox?.querySelectorAll('[role=option]');
                                     highlightedIndex = items ? Array.from(items).indexOf($el) : -1;
                                     activeDescendantId = $el.id;
                                 "
                             >
                                 <span class="fw-medium">{{ $brand->name }}</span>
                             </button>
                         @endforeach
                     </div>
                @endif
            </div>
        </div>
    @endif
</div>
