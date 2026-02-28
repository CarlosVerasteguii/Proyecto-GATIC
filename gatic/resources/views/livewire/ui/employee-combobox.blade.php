<div
    class="position-relative"
    x-data="{
        highlightedIndex: -1,
        activeDescendantId: null,
        open: $wire.entangle('showDropdown'),
        selectHighlighted() {
            const items = this.$refs.listbox?.querySelectorAll('[role=option]');
            if (items && items[this.highlightedIndex]) {
                items[this.highlightedIndex].click();
            }
        },
        moveHighlight(direction) {
            const items = this.$refs.listbox?.querySelectorAll('[role=option]');
            if (!items || items.length === 0) return;

            if (direction === 'down') {
                this.highlightedIndex = this.highlightedIndex < items.length - 1
                    ? this.highlightedIndex + 1
                    : 0;
            } else {
                this.highlightedIndex = this.highlightedIndex > 0
                    ? this.highlightedIndex - 1
                    : items.length - 1;
            }

            const active = items[this.highlightedIndex];
            this.activeDescendantId = active?.id ?? null;
            active?.scrollIntoView({ block: 'nearest' });
        }
    }"
    x-effect="if (!open) { activeDescendantId = null; highlightedIndex = -1 }"
    x-on:keydown.escape.prevent="open = false; activeDescendantId = null; highlightedIndex = -1; $refs.input?.blur()"
    x-on:click.away="open = false; activeDescendantId = null; highlightedIndex = -1"
    x-on:employee-combobox:focus-input.window="
        if ($event.detail?.inputId === '{{ $inputId }}') {
            $refs.input?.focus();
            open = false;
            activeDescendantId = null;
            highlightedIndex = -1;
        }
    "
>
    @if ($employeeId)
        {{-- Pill visual del empleado seleccionado --}}
        <div class="d-flex align-items-center gap-2 p-2 bg-success bg-opacity-10 border border-success rounded">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <span class="badge bg-success">
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ $employeeRpe }}
                </span>
                <span class="fw-medium">{{ $employeeName }}</span>
                @if($employeeDepartment)
                    <span class="text-muted small">({{ $employeeDepartment }})</span>
                @endif
            </div>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                wire:click="clearSelection"
                aria-label="Cambiar empleado"
                title="Cambiar empleado"
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
                type="text"
                class="form-control"
                placeholder="Buscar empleado por RPE o nombre..."
                wire:model.live.debounce.300ms="search"
                x-on:keydown.down.prevent="moveHighlight('down')"
                x-on:keydown.up.prevent="moveHighlight('up')"
                x-on:keydown.enter.prevent="selectHighlighted()"
                x-on:focus="open = true; highlightedIndex = -1; activeDescendantId = null"
                role="combobox"
                aria-haspopup="listbox"
                :aria-expanded="open ? 'true' : 'false'"
                aria-controls="{{ $listboxId }}"
                aria-autocomplete="list"
                :aria-activedescendant="activeDescendantId"
                autocomplete="off"
                id="{{ $inputId }}"
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
                aria-label="Lista de empleados"
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
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm mt-2"
                                    wire:click="retrySearch"
                                >
                                    Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div wire:loading.delay wire:target="search" class="p-3 text-muted small">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Buscando...
                    </div>

                    <div wire:loading.remove wire:target="search">
                        @if ($showMinCharsMessage)
                            <div class="p-3 text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Escribe al menos 2 caracteres
                            </div>
                        @elseif ($showNoResults)
                            <div class="p-2">
                                <div class="px-2 py-1 text-muted small">
                                    <i class="bi bi-search me-1"></i>
                                    Sin resultados
                                </div>
                                <button
                                    id="{{ $createOptionId }}"
                                    type="button"
                                    class="dropdown-item d-flex align-items-center gap-2 rounded"
                                    wire:click="openCreateEmployeeModal"
                                    wire:loading.attr="disabled"
                                    wire:target="openCreateEmployeeModal"
                                    role="option"
                                >
                                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                                    <span>Crear empleado</span>
                                </button>
                            </div>
                        @elseif ($employees->isNotEmpty())
                            @foreach ($employees as $index => $employee)
                                <button
                                    id="{{ $optionIdPrefix }}{{ $employee->id }}"
                                    type="button"
                                    class="dropdown-item d-flex flex-column align-items-start px-3 py-2"
                                    :class="{ 'bg-primary text-white': highlightedIndex === {{ $index }} }"
                                    :aria-selected="highlightedIndex === {{ $index }} ? 'true' : 'false'"
                                    wire:click="selectEmployee({{ $employee->id }})"
                                    role="option"
                                    x-on:mouseenter="highlightedIndex = {{ $index }}; activeDescendantId = $el.id"
                                >
                                    <span class="fw-medium">
                                        {{ $employee->rpe }} - {{ $employee->name }}
                                    </span>
                                    @if ($employee->department)
                                        <small class="opacity-75">
                                            {{ $employee->department }}
                                        </small>
                                    @endif
                                </button>
                            @endforeach
                        @endif
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
            x-on:click.self="$wire.closeCreateEmployeeModal()"
            x-on:keydown.escape.stop.prevent="$wire.closeCreateEmployeeModal()"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $createModalTitleId }}">Crear empleado</h5>
                        <button type="button" class="btn-close" wire:click="closeCreateEmployeeModal" aria-label="Cerrar"></button>
                    </div>

                    <form wire:submit="createEmployee">
                        <div class="modal-body">
                            @if (is_string($createErrorId) && $createErrorId !== '')
                                <x-ui.error-alert-with-id :error-id="$createErrorId" class="mb-3" />
                            @endif

                            <div class="mb-3">
                                <label for="{{ $createRpeInputId }}" class="form-label">
                                    RPE <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="{{ $createRpeInputId }}"
                                    type="text"
                                    class="form-control @error('createRpe') is-invalid @enderror"
                                    wire:model="createRpe"
                                    maxlength="255"
                                >
                                @error('createRpe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="{{ $createNameInputId }}" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="{{ $createNameInputId }}"
                                    type="text"
                                    class="form-control @error('createName') is-invalid @enderror"
                                    wire:model="createName"
                                    maxlength="255"
                                >
                                @error('createName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                wire:click="closeCreateEmployeeModal"
                                wire:loading.attr="disabled"
                                wire:target="createEmployee"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="createEmployee"
                            >
                                <span wire:loading.remove wire:target="createEmployee">Guardar</span>
                                <span wire:loading.inline wire:target="createEmployee">
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
