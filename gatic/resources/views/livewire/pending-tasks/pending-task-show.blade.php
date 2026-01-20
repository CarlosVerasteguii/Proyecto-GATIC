<div class="container position-relative">
    <x-ui.long-request />

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
                        <a href="{{ route('pending-tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                            Volver
                        </a>
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

            {{-- Lines Section --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        Renglones
                        <span class="badge bg-secondary ms-2">{{ $task->lines->count() }}</span>
                        @if (count($duplicates) > 0)
                            <span class="badge bg-warning text-dark ms-1" title="Duplicados detectados">
                                {{ count($duplicates) }} duplicados
                            </span>
                        @endif
                    </span>
                    @if ($task->isDraft())
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                wire:click="openAddLineModal"
                            >
                                Añadir renglón
                            </button>
                            @if ($task->lines->count() > 0)
                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    wire:click="markAsReady"
                                    wire:confirm="¿Marcar como lista? Ya no podrás editar los renglones."
                                >
                                    Marcar como lista
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if ($task->lines->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tipo</th>
                                        <th>Producto</th>
                                        <th>Identificador</th>
                                        <th>Empleado</th>
                                        <th>Nota</th>
                                        @if ($task->isDraft())
                                            <th class="text-end">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($task->lines as $line)
                                        <tr @class(['table-warning' => $this->isDuplicate($line->id)])>
                                            <td>{{ $line->order }}</td>
                                            <td>{{ $line->line_type->label() }}</td>
                                            <td>{{ $line->product->name ?? '-' }}</td>
                                            <td>
                                                {{ $line->identifier_display }}
                                                @if ($this->isDuplicate($line->id))
                                                    <span
                                                        class="badge bg-warning text-dark"
                                                        title="Este identificador está duplicado en la tarea"
                                                    >
                                                        Duplicado
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $line->employee->full_name ?? '-' }}</td>
                                            <td class="text-truncate" style="max-width: 200px;" title="{{ $line->note }}">
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
                                                        wire:confirm="¿Eliminar este renglón?"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            @if ($task->isDraft())
                                No hay renglones. Haz clic en "Añadir renglón" para comenzar.
                            @else
                                No hay renglones en esta tarea.
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Line Modal --}}
    @if ($showLineModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $editingLineId ? 'Editar renglón' : 'Añadir renglón' }}
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
                                    <div class="col-md-6">
                                        <label for="serial" class="form-label">Serial</label>
                                        <input
                                            type="text"
                                            id="serial"
                                            class="form-control @error('serial') is-invalid @enderror"
                                            wire:model="serial"
                                            placeholder="Número de serie"
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
                                        placeholder="Motivo o descripción del movimiento..."
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
                                {{ $editingLineId ? 'Actualizar' : 'Añadir' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @endif
</div>
