@php($attachments = $this->attachments)

<x-ui.section-card
    title="Adjuntos"
    subtitle="Evidencia documental privada asociada al registro actual."
    icon="bi-paperclip"
    bodyClass="trace-panel__body"
    class="trace-panel"
>
    <x-slot:actions>
        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
            {{ $attachments->total() }}
        </x-ui.badge>
        <x-ui.badge tone="warning" variant="compact" :with-rail="false">
            Privado
        </x-ui.badge>
    </x-slot:actions>

    @if ($showSuccessMessage)
        <div
            x-data
            x-init="setTimeout(() => $wire.hideSuccessMessage(), 3000)"
            class="alert alert-success alert-dismissible fade show trace-panel__alert"
            role="status"
            aria-live="polite"
        >
            {{ $successMessage }}
            <button type="button" class="btn-close" wire:click="hideSuccessMessage" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($showErrorMessage)
        <div class="alert alert-danger alert-dismissible fade show trace-panel__alert" role="alert" aria-live="polite">
            {{ $errorMessage }}
            <button type="button" class="btn-close" wire:click="hideErrorMessage" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($this->canManage)
        <form wire:submit="uploadAttachment" class="trace-panel__composer">
            <div class="trace-panel__eyebrow">Carga controlada</div>
            <h3 class="trace-panel__composer-title h6">Subir adjunto</h3>
            <p class="trace-panel__hint mb-3">
                Guarda archivos operativos con acceso restringido y nombre original visible solo dentro de la app.
            </p>

            <div class="mb-3">
                <label for="newFile" class="form-label small fw-semibold">Archivo</label>
                <input
                    type="file"
                    id="newFile"
                    name="attachment_file"
                    wire:model="newFile"
                    class="form-control @error('newFile') is-invalid @enderror"
                    accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx"
                >
                @error('newFile')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Archivos permitidos: PDF, imágenes (PNG, JPG, WEBP), texto, Word y Excel. Máx: 10 MB.
                </div>
            </div>

            <div class="trace-panel__composer-actions">
                <span wire:loading wire:target="newFile" class="trace-panel__metric">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Cargando archivo…
                </span>

                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="newFile,uploadAttachment">
                    <span wire:loading.remove wire:target="uploadAttachment">
                        <i class="bi bi-upload me-1" aria-hidden="true"></i>Subir adjunto
                    </span>
                    <span wire:loading wire:target="uploadAttachment">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Subiendo…
                    </span>
                </button>
            </div>
        </form>
    @endif

    @if ($attachments->isEmpty())
        <div class="trace-panel__empty">
            <x-ui.empty-state
                icon="bi-paperclip"
                title="Sin adjuntos"
                description="Aún no hay evidencia documental asociada a este detalle."
                compact
            />
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 trace-attachments-table">
                <thead class="table-gatic-head">
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
                        <tr wire:key="attachment-{{ $attachment->id }}">
                            <td class="min-w-0">
                                <a
                                    href="{{ route('attachments.download', $attachment->id) }}"
                                    class="trace-attachment__link text-decoration-none"
                                    title="Descargar"
                                >
                                    <i class="bi bi-file-earmark" aria-hidden="true"></i>
                                    <span class="text-break">{{ Str::limit($attachment->original_name, 52) }}</span>
                                </a>

                                <div class="trace-attachment__mobile-meta d-md-none mt-2">
                                    <div>{{ $attachment->human_size }}</div>
                                    <div>{{ $attachment->uploader?->name ?? 'Usuario desconocido' }}</div>
                                    <div>{{ $attachment->created_at->format('d/m/Y H:i') }}</div>
                                </div>
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
                            <td class="text-end text-nowrap">
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
                                        wire:target="deleteAttachment"
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

        <div class="trace-panel__pagination">
            {{ $attachments->links() }}
        </div>
    @endif
</x-ui.section-card>
