<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card mb-4">
                <div class="card-header">Prueba Livewire (smoke)</div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">Toasts (Livewire)</div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-success btn-sm" wire:click="toastSuccessDemo">
                                        Toast &eacute;xito
                                    </button>

                                    <button type="button" class="btn btn-danger btn-sm" wire:click="toastErrorDemo">
                                        Toast error
                                    </button>

                                    <button type="button" class="btn btn-primary btn-sm" wire:click="toggleWithUndo">
                                        Toggle con "Deshacer"
                                    </button>
                                </div>

                                <div class="mt-3 small text-muted">
                                    Estado actual (toggle): <strong>{{ $toggle ? 'ON' : 'OFF' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">Acci&oacute;n lenta (&gt;3s) + Cancelar</div>

                                <div class="position-relative">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="small text-muted">Resultado actual:</div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="slowOperation">
                                            Ejecutar (5s)
                                        </button>
                                    </div>

                                    <div class="p-2 border rounded bg-body">
                                        <div class="fw-semibold">{{ $slowResult }}</div>
                                        <div class="small text-muted">Versi&oacute;n: {{ $slowResultVersion }}</div>
                                    </div>

                                    <x-ui.long-request target="slowOperation" />
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <x-ui.poll method="pollTick" class="border rounded p-3">
                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-semibold">Polling + "Actualizado hace Xs"</div>
                                        <div class="small text-muted">Polls recibidos: <strong>{{ $pollCount }}</strong></div>
                                    </div>

                                    <x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />
                                </div>
                            </x-ui.poll>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-2">B&aacute;sico</div>

                                <p class="mb-3">Contador: <strong>{{ $count }}</strong></p>

                                <button type="button" class="btn btn-primary" wire:click="increment">
                                    +1
                                </button>
                            </div>
                        </div>

                        @can('inventory.manage')
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-2">EmployeeCombobox (Story 4.2)</div>
                                <p class="small text-muted mb-3">
                                    Selector reusable de empleados con autocomplete. Busca por RPE o nombre.
                                </p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Seleccionar empleado</label>
                                        <livewire:ui.employee-combobox wire:model="selectedEmployeeId" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Estado actual</label>
                                        <div class="p-2 border rounded bg-body">
                                            <code>employee_id: {{ $selectedEmployeeId ?? 'null' }}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
