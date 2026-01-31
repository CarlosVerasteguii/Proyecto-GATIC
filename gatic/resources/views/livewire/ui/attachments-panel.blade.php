<div class="card mt-3">
    @php($attachments = $this->attachments)
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-paperclip me-1"></i>Adjuntos</span>
        <span class="badge bg-secondary">{{ $attachments->total() }}</span>
    </div>
    <div class="card-body">
        {{-- Success message --}}
        @if ($showSuccessMessage)
            <div
                x-data
                x-init="setTimeout(() => $wire.hideSuccessMessage(), 3000)"
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >
                {{ $successMessage }}
                <button type="button" class="btn-close" wire:click="hideSuccessMessage" aria-label="Cerrar"></button>
            </div>
        @endif

        {{-- Error message --}}
        @if ($showErrorMessage)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errorMessage }}
                <button type="button" class="btn-close" wire:click="hideErrorMessage" aria-label="Cerrar"></button>
            </div>
        @endif

        {{-- Upload form (Admin/Editor only) --}}
        @if ($this->canManage)
            <form wire:submit="uploadAttachment" class="mb-3">
                <div class="mb-2">
                    <label for="newFile" class="form-label visually-hidden">Nuevo adjunto</label>
                    <input
                        type="file"
                        id="newFile"
                        wire:model="newFile"
                        class="form-control @error('newFile') is-invalid @enderror"
                        accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx"
                    >
                    @error('newFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Archivos permitidos: PDF, imágenes (PNG, JPG, WEBP), texto, Word, Excel. Máx: 10 MB.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="newFile,uploadAttachment">
                    <span wire:loading.remove wire:target="uploadAttachment">
                        <i class="bi bi-upload me-1"></i>Subir adjunto
                    </span>
                    <span wire:loading wire:target="uploadAttachment">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Subiendo...
                    </span>
                </button>
                <span wire:loading wire:target="newFile" class="text-muted small ms-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Cargando archivo...
                </span>
            </form>
        @endif

        {{-- Attachments list --}}
        @if ($attachments->isEmpty())
            <p class="text-muted mb-0">Sin adjuntos.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th class="text-center d-none d-md-table-cell">Tamaño</th>
                            <th class="d-none d-md-table-cell">Subido por</th>
                            <th class="text-end">Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attachments as $attachment)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('attachments.download', $attachment->id) }}"
                                        class="text-decoration-none"
                                        title="Descargar"
                                    >
                                        <i class="bi bi-file-earmark me-1"></i>{{ Str::limit($attachment->original_name, 40) }}
                                    </a>
                                </td>
                                <td class="text-center d-none d-md-table-cell text-muted small">
                                    {{ $attachment->human_size }}
                                </td>
                                <td class="d-none d-md-table-cell text-muted small">
                                    {{ $attachment->uploader?->name ?? 'Usuario desconocido' }}
                                </td>
                                <td class="text-end text-muted small">
                                    {{ $attachment->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a
                                        href="{{ route('attachments.download', $attachment->id) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Descargar"
                                        aria-label="Descargar {{ $attachment->original_name }}"
                                    >
                                        <i class="bi bi-download" aria-hidden="true"></i>
                                    </a>
                                    @if ($this->canManage)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Eliminar"
                                            aria-label="Eliminar {{ $attachment->original_name }}"
                                            wire:click="deleteAttachment({{ $attachment->id }})"
                                            wire:confirm="¿Estás seguro de eliminar este adjunto? Esta acción no se puede deshacer."
                                            wire:loading.attr="disabled"
                                        >
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $attachments->links() }}
            </div>
        @endif
    </div>
</div>
