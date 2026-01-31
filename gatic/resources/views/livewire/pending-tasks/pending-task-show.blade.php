<div class="container position-relative">
    <x-ui.long-request target="finalizeTask,enterProcessMode,initProcessModeUi" />

    @if ($task)
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            {{-- Task Header --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Tarea #{{ $task->id }}</span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge {{ $task->status->badgeClass() }}">
                            {{ $task->status->label() }}
                        </span>
                        @if ($isProcessMode)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="exitProcessMode"
                            >
                                Salir de Procesar
                            </button>
                        @else
                            <a href="{{ route('pending-tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                                Volver
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>Tipo:</strong> {{ $task->type->label() }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Creador:</strong> {{ $task->creator->name ?? '-' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Fecha:</strong> {{ $task->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @if ($task->description)
                        <div class="mt-2">
                            <strong>Descripción:</strong>
                            <p class="mb-0 text-muted">{{ $task->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Lock Status Banner (visible to third parties) --}}
            @if ($this->canProcess())
                @php
                    $currentUserId = auth()->id();
                    $hasActiveLock = $task->hasActiveLock();
                    $isMyLock = $task->isLockedBy($currentUserId);
                    $isOtherLock = $hasActiveLock && !$isMyLock;
                    $isAdmin = $this->isAdmin();
                @endphp
                <div class="alert {{ $isOtherLock ? 'alert-warning' : ($isMyLock ? 'alert-info' : 'alert-secondary') }} mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            @if ($isMyLock)
                                <i class="bi bi-lock-fill"></i>
                                <span>
                                    <strong>Bloqueada por ti</strong>
                                    @if ($task->locked_at)
                                        <span class="text-muted small ms-2">desde {{ $task->locked_at->diffForHumans() }}</span>
                                    @endif
                                </span>
                            @elseif ($isOtherLock)
                                <i class="bi bi-lock-fill"></i>
                                <span>
                                    <strong>Bloqueada por {{ $task->lockedBy?->name ?? 'otro usuario' }}</strong>
                                    @if ($task->locked_at)
                                        <span class="text-muted small ms-2">desde {{ $task->locked_at->diffForHumans() }}</span>
                                    @endif
                                </span>
                            @else
                                <i class="bi bi-unlock"></i>
                                <span><strong>Libre</strong> - Nadie está procesando esta tarea</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($isMyLock)
                                <span class="badge bg-primary">Tú tienes el lock</span>
                            @elseif ($isOtherLock)
                                <span class="badge bg-warning text-dark">Solo lectura</span>
                            @endif
                        </div>
                    </div>

                    {{-- Admin Override Actions (AC1-AC4) --}}
                    @if ($isAdmin && $isOtherLock)
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Como Admin, puedes forzar acciones sobre este lock.
                            </small>
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    wire:click="forceReleaseLock"
                                    wire:confirm="¿Forzar liberación? El usuario {{ $task->lockedBy?->name ?? 'actual' }} perderá el lock."
                                >
                                    <i class="bi bi-unlock me-1"></i>
                                    Forzar liberación
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="forceClaimLock"
                                    wire:confirm="¿Forzar reclamo? Tomarás el control del lock y el usuario {{ $task->lockedBy?->name ?? 'actual' }} lo perderá."
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Forzar reclamo
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Lock Lost Banner (shown when user loses lock during processing) --}}
            @if ($lockLost)
                <div class="alert alert-danger mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>
                                <strong>Lock perdido</strong> - Tu sesión de procesamiento ha expirado o alguien más reclamó la tarea.
                            </span>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-danger"
                            wire:click="retryLock"
                        >
                            <i class="bi bi-arrow-repeat me-1"></i>
                            Reintentar claim
                        </button>
                    </div>
                </div>
            @endif

            {{-- Finalize Result Summary --}}
            @if ($finalizeResult)
                <div class="alert {{ $finalizeResult['error_count'] > 0 ? 'alert-warning' : 'alert-success' }} mb-4">
                    <h5 class="alert-heading mb-2">Resumen de Finalizacion</h5>
                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <span class="badge bg-success fs-6">{{ $finalizeResult['applied_count'] }}</span>
                            <span class="ms-1">Aplicados</span>
                        </div>
                        @if ($finalizeResult['error_count'] > 0)
                            <div>
                                <span class="badge bg-danger fs-6">{{ $finalizeResult['error_count'] }}</span>
                                <span class="ms-1">Errores</span>
                            </div>
                        @endif
                        @if ($finalizeResult['skipped_count'] > 0)
                            <div>
                                <span class="badge bg-secondary fs-6">{{ $finalizeResult['skipped_count'] }}</span>
                                <span class="ms-1">Ya aplicados</span>
                            </div>
                        @endif
                    </div>
                    @if ($finalizeResult['error_count'] > 0)
                        <hr>
                        <p class="mb-0 small">
                            Los renglones con error no fueron aplicados. Puedes corregirlos y volver a intentar.
                        </p>
                    @endif
                </div>
            @endif

            {{-- Lines Section --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span>Renglones</span>
                        <span class="badge bg-secondary">{{ $task->lines->count() }}</span>
                        @if (count($duplicates) > 0)
                            <span class="badge bg-warning text-dark" title="Duplicados detectados">
                                {{ count($duplicates) }} duplicados
                            </span>
                        @endif

                        {{-- Line status summary in process mode --}}
                        @if ($isProcessMode)
                            <span class="border-start ps-2 ms-2"></span>
                            @if ($lineStatusSummary['applied'] > 0)
                                <span class="badge bg-success" title="Aplicados">
                                    {{ $lineStatusSummary['applied'] }} aplicados
                                </span>
                            @endif
                            @if ($lineStatusSummary['pending'] > 0)
                                <span class="badge bg-secondary" title="Pendientes">
                                    {{ $lineStatusSummary['pending'] }} pendientes
                                </span>
                            @endif
                            @if ($lineStatusSummary['error'] > 0)
                                <span class="badge bg-danger" title="Con error">
                                    {{ $lineStatusSummary['error'] }} errores
                                </span>
                            @endif
                        @endif
                    </div>

                    {{-- Action buttons --}}
                    <div class="d-flex gap-2 flex-wrap">
                        @if ($task->isDraft())
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                wire:click="openAddLineModal"
                            >
                                Agregar renglón
                            </button>
                            @if ($task->lines->count() > 0)
                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    wire:click="markAsReady"
                                    wire:confirm="Marcar como lista? Ya no podras editar los renglones."
                                >
                                    Marcar como lista
                                </button>
                            @endif
                        @elseif ($this->canProcess() && !$isProcessMode)
                            @php
                                $canStartProcess = !$task->isLockedByOther(auth()->id());
                            @endphp
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                wire:click="enterProcessMode"
                                wire:loading.attr="disabled"
                                wire:target="enterProcessMode"
                                @if (!$canStartProcess) disabled title="Bloqueada por otro usuario" @endif
                            >
                                <span wire:loading.remove wire:target="enterProcessMode">Procesar</span>
                                <span wire:loading.inline wire:target="enterProcessMode">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Procesando...
                                </span>
                            </button>
                        @elseif ($isProcessMode)
                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                wire:click="showFinalizeConfirm"
                                @if ($lockLost || !$hasLock || !$processModeReady) disabled title="{{ !$processModeReady ? 'Cargando...' : 'No tienes el lock' }}" @endif
                            >
                                Finalizar
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if ($task->lines->count() > 0)
                        @if ($isProcessMode && !$processModeReady)
                            <div
                                class="border rounded p-3 bg-light"
                                wire:init="initProcessModeUi"
                            >
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                                        <span class="fw-semibold">Cargando modo Procesar…</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        wire:click="initProcessModeUi"
                                        wire:loading.attr="disabled"
                                        wire:target="initProcessModeUi"
                                    >
                                        Reintentar
                                    </button>
                                </div>
                                <x-ui.skeleton variant="lines" :lines="6" />
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            @if ($isProcessMode)
                                                <th>Estado</th>
                                            @endif
                                            <th>Tipo</th>
                                            <th>Producto</th>
                                            <th>Identificador</th>
                                            <th>Empleado</th>
                                            <th>Nota</th>
                                            @if ($task->isDraft() || $isProcessMode)
                                                <th class="text-end">Acciones</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($task->lines as $line)
                                            <tr @class([
                                                'table-warning' => $this->isDuplicate($line->id) && !$isProcessMode,
                                                'table-success' => $isProcessMode && $line->line_status->value === 'applied',
                                                'table-danger' => $isProcessMode && $line->line_status->value === 'error',
                                            ])>
                                                <td>{{ $line->order }}</td>

                                                @if ($isProcessMode)
                                                    <td>
                                                        <span class="badge {{ $line->line_status->badgeClass() }}">
                                                            {{ $line->line_status->label() }}
                                                        </span>
                                                    </td>
                                                @endif

                                                <td>{{ $line->line_type->label() }}</td>
                                                <td>{{ $line->product->name ?? '-' }}</td>
                                                <td>
                                                    {{ $line->identifier_display }}
                                                    @if ($this->isDuplicate($line->id))
                                                        <span
                                                            class="badge bg-warning text-dark"
                                                            title="Este identificador esta duplicado en la tarea"
                                                        >
                                                            Duplicado
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>{{ $line->employee->full_name ?? '-' }}</td>
                                                <td class="text-truncate" style="max-width: 150px;" title="{{ $line->note }}">
                                                    {{ $line->note }}
                                                </td>

                                                @if ($task->isDraft())
                                                    <td class="text-end">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary"
                                                            wire:click="openEditLineModal({{ $line->id }})"
                                                        >
                                                            Editar
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            wire:click="removeLine({{ $line->id }})"
                                                            wire:confirm="Eliminar este renglón?"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </td>
                                                @elseif ($isProcessMode)
                                                    <td class="text-end">
                                                    @if ($line->line_status->value === 'applied')
                                                        <span class="text-success small">
                                                            <i class="bi bi-check-circle"></i> Aplicado
                                                        </span>
                                                    @elseif ($lockLost || !$hasLock)
                                                        <span class="text-muted small">
                                                            <i class="bi bi-lock"></i> Sin lock
                                                        </span>
                                                    @else
                                                        <div class="btn-group btn-group-sm">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-primary"
                                                                wire:click="openProcessLineModal({{ $line->id }})"
                                                                title="Editar"
                                                            >
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-info"
                                                                wire:click="validateLine({{ $line->id }})"
                                                                title="Validar"
                                                            >
                                                                <i class="bi bi-check2"></i>
                                                            </button>
                                                            @if ($line->line_status->value === 'error')
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-secondary"
                                                                    wire:click="clearLineError({{ $line->id }})"
                                                                    title="Limpiar error"
                                                                >
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                        {{-- Error message row in process mode --}}
                                        @if ($isProcessMode && $line->line_status->value === 'error' && $line->error_message)
                                            <tr class="table-danger">
                                                <td></td>
                                                <td colspan="{{ $task->isDraft() || $isProcessMode ? 7 : 6 }}" class="text-danger small py-1">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    {{ $line->error_message }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">
                            @if ($task->isDraft())
                                No hay renglones. Haz clic en "Agregar renglón" para comenzar.
                            @else
                                No hay renglones en esta tarea.
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Line Modal (Draft mode) --}}
    @if ($showLineModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $editingLineId ? 'Editar renglón' : 'Agregar renglón' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <form wire:submit="saveLine">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="productId" class="form-label">
                                        Producto <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="productId"
                                        class="form-select @error('product_id') is-invalid @enderror"
                                        wire:model.live="productId"
                                        required
                                    >
                                        <option value="">Seleccionar...</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product['id'] }}">
                                                {{ $product['name'] }}
                                                ({{ $product['is_serialized'] ? 'Serializado' : 'Por cantidad' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="lineType" class="form-label">
                                        Tipo de renglón <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="lineType"
                                        class="form-select @error('line_type') is-invalid @enderror"
                                        wire:model.live="lineType"
                                        required
                                    >
                                        <option value="">Seleccionar...</option>
                                        @foreach ($lineTypes as $lt)
                                            <option value="{{ $lt->value }}">{{ $lt->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('line_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if ($lineType === 'serialized')
                                    @if ($editingLineId)
                                        <div class="col-md-6">
                                            <label for="serial" class="form-label">Serial</label>
                                            <input
                                                type="text"
                                                id="serial"
                                                class="form-control @error('serial') is-invalid @enderror"
                                                wire:model="serial"
                                                placeholder="Numero de serie"
                                            />
                                            @error('serial')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="assetTag" class="form-label">Asset Tag</label>
                                            <input
                                                type="text"
                                                id="assetTag"
                                                class="form-control @error('asset_tag') is-invalid @enderror"
                                                wire:model="assetTag"
                                                placeholder="Etiqueta de activo"
                                            />
                                            @error('asset_tag')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <label for="serializedBulkInput" class="form-label">
                                                Series (1 por linea) <span class="text-danger">*</span>
                                            </label>
                                            <textarea
                                                id="serializedBulkInput"
                                                class="form-control @error('serializedBulkInput') is-invalid @enderror"
                                                wire:model.live.debounce.500ms="serializedBulkInput"
                                                rows="6"
                                                placeholder="Ej:\nABC123\nABC124\nABC125"
                                                required
                                            ></textarea>
                                            @error('serializedBulkInput')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Se ignoran lineas vacias. Si hay alguna invalida, no se puede guardar.
                                            </div>
                                        </div>

                                        @if ($serializedBulkCount > 0)
                                            <div class="col-12">
                                                <div class="d-flex flex-wrap gap-3 align-items-center small">
                                                    <span class="text-muted">Total: <strong>{{ $serializedBulkCount }}</strong></span>
                                                    @if ($serializedBulkOkCount > 0)
                                                        <span class="text-success">OK: <strong>{{ $serializedBulkOkCount }}</strong></span>
                                                    @endif
                                                    @if ($serializedBulkDuplicateCount > 0)
                                                        <span class="text-warning">
                                                            Duplicadas: <strong>{{ $serializedBulkDuplicateCount }}</strong>
                                                        </span>
                                                    @endif
                                                    @if ($serializedBulkInvalidCount > 0)
                                                        <span class="text-danger">
                                                            Invalidas: <strong>{{ $serializedBulkInvalidCount }}</strong>
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($serializedBulkLimitError)
                                                    <div class="alert alert-danger py-2 my-2 small mb-0">
                                                        {{ $serializedBulkLimitError }}
                                                    </div>
                                                @endif

                                                @if (count($serializedBulkPreview) > 0)
                                                    <div class="border rounded p-2 mt-2" style="max-height: 220px; overflow: auto;">
                                                        <ul class="list-unstyled mb-0 small">
                                                            @foreach ($serializedBulkPreview as $item)
                                                                <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                                                                    <div class="flex-grow-1">
                                                                        <strong>L{{ $item['line'] }}:</strong>
                                                                        <span class="font-monospace">{{ $item['value'] }}</span>
                                                                        @if ($item['message'])
                                                                            <div class="text-muted">{{ $item['message'] }}</div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <span @class([
                                                                            'badge',
                                                                            'bg-success' => $item['status'] === 'ok',
                                                                            'bg-warning text-dark' => $item['status'] === 'duplicate',
                                                                            'bg-danger' => $item['status'] === 'invalid',
                                                                        ])>
                                                                            {{ $item['status_label'] }}
                                                                        </span>
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @endif

                                @if ($lineType === 'quantity')
                                    <div class="col-md-6">
                                        <label for="quantity" class="form-label">
                                            Cantidad <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="quantity"
                                            class="form-control @error('quantity') is-invalid @enderror"
                                            wire:model="quantity"
                                            min="1"
                                            required
                                        />
                                        @error('quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Empleado <span class="text-danger">*</span>
                                    </label>
                                    <livewire:ui.employee-combobox
                                        :selected-employee-id="$employeeId"
                                        event-name="employee-selected"
                                        :key="'employee-combobox-' . ($editingLineId ?? 'new')"
                                    />
                                    @error('employee_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">
                                        Nota <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="note"
                                        class="form-control @error('note') is-invalid @enderror"
                                        wire:model="note"
                                        rows="2"
                                        required
                                        placeholder="Motivo o descripcion del movimiento..."
                                    ></textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ $editingLineId ? 'Actualizar' : 'Agregar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Process Line Edit Modal --}}
    @if ($showProcessLineModal)
        @php
            $editLine = $task->lines->firstWhere('id', $editingProcessLineId);
        @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Renglon</h5>
                        <button type="button" class="btn-close" wire:click="closeProcessLineModal"></button>
                    </div>
                    <form wire:submit="saveProcessLine">
                        <div class="modal-body">
                            @if ($editLine)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Producto</label>
                                    <div class="form-control-plaintext">{{ $editLine->product->name ?? '-' }}</div>
                                </div>

                                @if ($editLine->isSerialized())
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="processLineSerial" class="form-label">Serial</label>
                                            <input
                                                type="text"
                                                id="processLineSerial"
                                                class="form-control @error('processLineSerial') is-invalid @enderror"
                                                wire:model="processLineSerial"
                                                placeholder="Numero de serie"
                                            />
                                            @error('processLineSerial')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="processLineAssetTag" class="form-label">Asset Tag</label>
                                            <input
                                                type="text"
                                                id="processLineAssetTag"
                                                class="form-control @error('processLineAssetTag') is-invalid @enderror"
                                                wire:model="processLineAssetTag"
                                                placeholder="Etiqueta de activo"
                                            />
                                            @error('processLineAssetTag')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-3">
                                        <label for="processLineQuantity" class="form-label">
                                            Cantidad <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="processLineQuantity"
                                            class="form-control @error('processLineQuantity') is-invalid @enderror"
                                            wire:model="processLineQuantity"
                                            min="1"
                                            required
                                        />
                                        @error('processLineQuantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">
                                        Empleado <span class="text-danger">*</span>
                                    </label>
                                    <livewire:ui.employee-combobox
                                        :selected-employee-id="$processLineEmployeeId"
                                        event-name="process-employee-selected"
                                        :key="'process-employee-combobox-' . $editingProcessLineId"
                                    />
                                    @error('processLineEmployeeId')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="processLineNote" class="form-label">
                                        Nota <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="processLineNote"
                                        class="form-control @error('processLineNote') is-invalid @enderror"
                                        wire:model="processLineNote"
                                        rows="2"
                                        required
                                        placeholder="Motivo o descripcion del movimiento..."
                                    ></textarea>
                                    @error('processLineNote')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeProcessLineModal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Finalize Confirmation Modal --}}
    @if ($showFinalizeConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Finalizacion</h5>
                        <button type="button" class="btn-close" wire:click="hideFinalizeConfirm"></button>
                    </div>
                    <div class="modal-body">
                        <p>Estás a punto de finalizar esta tarea. Se aplicarán los movimientos de los renglones válidos.</p>

                        <div class="alert alert-info mb-0">
                            <strong>Resumen:</strong>
                            <ul class="mb-0 mt-2">
                                <li>
                                    <strong>{{ $lineStatusSummary['pending'] + $lineStatusSummary['processing'] }}</strong>
                                    renglones pendientes de aplicar
                                </li>
                                @if ($lineStatusSummary['applied'] > 0)
                                    <li>
                                        <strong>{{ $lineStatusSummary['applied'] }}</strong>
                                        renglones ya aplicados (se omitirán)
                                    </li>
                                @endif
                                @if ($lineStatusSummary['error'] > 0)
                                    <li class="text-danger">
                                        <strong>{{ $lineStatusSummary['error'] }}</strong>
                                        renglones con error (se intentarán aplicar)
                                    </li>
                                @endif
                            </ul>
                        </div>

                        @if (count($duplicates) > 0)
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Advertencia:</strong> Hay {{ count($duplicates) }} duplicados en la tarea.
                                Los renglones duplicados no seran aplicados.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="hideFinalizeConfirm">
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success"
                            wire:click="finalizeTask"
                        >
                            Finalizar Tarea
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @endif

    {{-- Heartbeat script for lock renewal with idle guard --}}
    @if ($isProcessMode && $hasLock)
        @script
        <script>

                const HEARTBEAT_INTERVAL_MS = {{ (int) config('gatic.ui.polling.locks_heartbeat_interval_s', 10) * 1000 }};
                const IDLE_GUARD_MS = {{ (int) config('gatic.pending_tasks.locks.idle_guard_s', 120) * 1000 }};

                let lastActivityAt = Date.now();
                let heartbeatTimer = null;

                // Track user activity with debounce
                const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

                function updateActivity() {
                    lastActivityAt = Date.now();
                }

                activityEvents.forEach(event => {
                    document.addEventListener(event, updateActivity, { passive: true });
                });

                function sendHeartbeat() {
                    // Only send if tab is visible and user was active recently
                    if (document.visibilityState !== 'visible') {
                        return;
                    }

                    const idleTime = Date.now() - lastActivityAt;
                    if (idleTime > IDLE_GUARD_MS) {
                        // User is idle - don't renew lock
                        return;
                    }

                    // Call Livewire heartbeat method
                    $wire.heartbeat();
                }

                // Start heartbeat interval
                heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL_MS);

                // Cleanup when Livewire removes/replaces this component's DOM
                const componentRoot = $wire.$el;
                let unhook = null;

                function cleanup() {
                    if (heartbeatTimer) {
                        clearInterval(heartbeatTimer);
                        heartbeatTimer = null;
                    }

                    activityEvents.forEach(event => {
                        document.removeEventListener(event, updateActivity);
                    });

                    if (typeof unhook === 'function') {
                        unhook();
                        unhook = null;
                    }
                }

                if (window.Livewire?.hook) {
                    unhook = Livewire.hook('morph.removing', ({ el }) => {
                        if (el !== componentRoot) return;
                        cleanup();
                    });
                }

        </script>
        @endscript
    @endif
</div>
