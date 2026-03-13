<div class="container position-relative">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <x-ui.detail-header
                title="Nueva tarea pendiente"
                subtitle="Elige entre un flujo manual editable o una captura rápida según el tipo de operación."
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Operaciones', 'url' => route('pending-tasks.index')],
                        ['label' => 'Nueva tarea', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.badge tone="primary" variant="compact" :with-rail="false">Captura manual</x-ui.badge>
                    @if (is_array($selectedTypeCard))
                        <x-ui.badge :tone="$selectedTypeCard['direction_tone']" variant="compact" :with-rail="false">
                            {{ $selectedTypeCard['label'] }}
                        </x-ui.badge>
                    @endif
                </x-slot:status>

                <x-slot:actions>
                    <a href="{{ route('pending-tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Volver a tareas
                    </a>
                </x-slot:actions>
            </x-ui.detail-header>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-8">
                    <form wire:submit="save" novalidate>
                        <x-ui.section-card
                            title="Crear tarea manual"
                            subtitle="Este flujo crea una tarea editable para agregar y revisar renglones antes de procesarla."
                            icon="bi-list-task"
                            class="mb-4"
                        >
                            @if ($errors->any())
                                <div class="alert alert-danger mb-4" role="alert" aria-live="polite">
                                    <div class="fw-semibold mb-2">Revisa los datos capturados.</div>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="alert alert-info d-flex align-items-start gap-2 mb-4" role="alert">
                                <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
                                <div>
                                    Usa esta opción cuando necesites construir la tarea paso a paso, revisar renglones y dejarla lista
                                    antes de entrar a “Procesar”.
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-12 col-lg-6">
                                    <label for="type" class="form-label">
                                        Tipo de operación <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="type"
                                        name="type"
                                        class="form-select @error('type') is-invalid @enderror"
                                        wire:model.live="type"
                                        autocomplete="off"
                                        required
                                    >
                                        <option value="">Seleccionar…</option>
                                        @foreach ($types as $typeOption)
                                            <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">
                                        Selecciona el flujo operativo que vas a preparar antes de agregar renglones.
                                    </div>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-lg-6">
                                    <div class="border rounded-3 h-100 p-3 bg-body-tertiary" aria-live="polite">
                                        <div class="small text-body-secondary text-uppercase fw-semibold mb-2">
                                            Vista previa del flujo
                                        </div>

                                        @if (is_array($selectedTypeCard))
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                                <span class="fw-semibold">{{ $selectedTypeCard['label'] }}</span>
                                                <x-ui.badge
                                                    :tone="$selectedTypeCard['direction_tone']"
                                                    variant="compact"
                                                    :with-rail="false"
                                                >
                                                    {{ $selectedTypeCard['direction_label'] }}
                                                </x-ui.badge>
                                            </div>
                                            <div class="small text-body-secondary mb-2">{{ $selectedTypeCard['summary'] }}</div>
                                            <div class="small text-body-secondary mb-2">{{ $selectedTypeCard['flow_hint'] }}</div>
                                            <div class="small">
                                                <strong>Soporta:</strong> {{ $selectedTypeCard['line_support'] }}
                                            </div>
                                            @if (is_string($selectedTypeCard['quick_hint']))
                                                <div class="small text-body-secondary mt-2">{{ $selectedTypeCard['quick_hint'] }}</div>
                                            @endif
                                        @else
                                            <div class="text-body-secondary">
                                                Selecciona un tipo para ver qué clase de renglones admite y cuándo conviene usar
                                                captura rápida.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">Descripción operativa</label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        class="form-control @error('description') is-invalid @enderror"
                                        wire:model.blur="description"
                                        rows="4"
                                        maxlength="5000"
                                        autocomplete="off"
                                        placeholder="Ej. Regularización de préstamo devuelto en almacén…"
                                    ></textarea>
                                    <div class="form-text">
                                        Usa este campo para dejar contexto visible al equipo antes de agregar renglones.
                                    </div>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </x-ui.section-card>

                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <a href="{{ route('pending-tasks.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                                Cancelar
                            </a>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="save"
                            >
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                                    Crear tarea
                                </span>
                                <span wire:loading.inline wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Creando…
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-12 col-lg-4">
                    <x-ui.section-card
                        title="Referencia rápida"
                        subtitle="Resumen de los tipos disponibles para que elijas el flujo correcto."
                        icon="bi-compass"
                        class="mb-4"
                    >
                        <div class="d-flex flex-column gap-3">
                            @foreach ($typeCards as $typeCard)
                                <div @class([
                                    'border rounded-3 p-3',
                                    'bg-body-tertiary border-primary-subtle' => $type === $typeCard['value'],
                                ])>
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div class="fw-semibold">{{ $typeCard['label'] }}</div>
                                        <x-ui.badge
                                            :tone="$typeCard['direction_tone']"
                                            variant="compact"
                                            :with-rail="false"
                                        >
                                            {{ $typeCard['direction_label'] }}
                                        </x-ui.badge>
                                    </div>
                                    <div class="small text-body-secondary mt-2">{{ $typeCard['summary'] }}</div>
                                    <div class="small text-body-secondary mt-2">{{ $typeCard['flow_hint'] }}</div>
                                    <div class="small mt-2">
                                        <strong>Soporta:</strong> {{ $typeCard['line_support'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card
                        title="Atajos operativos"
                        subtitle="Cuando solo necesitas registrar el intake mínimo y seguir operando."
                        icon="bi-lightning-charge"
                    >
                        <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div>
                                    <div class="fw-semibold">Carga rápida</div>
                                    <div class="small text-body-secondary">
                                        Úsala para entradas express, incluyendo seriales nuevos o productos provisionales.
                                    </div>
                                </div>
                                <livewire:pending-tasks.quick-stock-in :key="'quick-stock-in-create-page'" />
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 bg-body-tertiary">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div>
                                    <div class="fw-semibold">Retiro rápido</div>
                                    <div class="small text-body-secondary">
                                        Úsalo para bajas urgentes por seriales o por producto y cantidad.
                                    </div>
                                </div>
                                <livewire:pending-tasks.quick-retirement :key="'quick-retirement-create-page'" />
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            </div>
        </div>
    </div>
</div>
