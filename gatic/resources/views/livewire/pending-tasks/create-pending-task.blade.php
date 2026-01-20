<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Nueva Tarea Pendiente</span>
                        <a href="{{ route('pending-tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                            Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label for="type" class="form-label">
                                Tipo de operación <span class="text-danger">*</span>
                            </label>
                            <select
                                id="type"
                                class="form-select @error('type') is-invalid @enderror"
                                wire:model="type"
                                required
                            >
                                <option value="">Seleccionar...</option>
                                @foreach ($types as $typeOption)
                                    <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción (opcional)</label>
                            <textarea
                                id="description"
                                class="form-control @error('description') is-invalid @enderror"
                                wire:model="description"
                                rows="3"
                                placeholder="Descripción de la tarea..."
                            ></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('pending-tasks.index') }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Crear tarea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
