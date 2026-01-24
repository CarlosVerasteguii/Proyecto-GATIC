<div class="card mt-3">
    @php($notes = $this->notes)
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Notas</span>
        <span class="badge bg-secondary">{{ $notes->total() }}</span>
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
                Nota guardada correctamente.
                <button type="button" class="btn-close" wire:click="hideSuccessMessage" aria-label="Cerrar"></button>
            </div>
        @endif

        {{-- Create note form (Admin/Editor only) --}}
        @if ($this->canCreate)
            <form wire:submit="createNote" class="mb-3">
                <div class="mb-2">
                    <label for="newNoteBody" class="form-label visually-hidden">Nueva nota</label>
                    <textarea
                        id="newNoteBody"
                        wire:model="newNoteBody"
                        class="form-control @error('newNoteBody') is-invalid @enderror"
                        rows="3"
                        placeholder="Escribe una nota..."
                        maxlength="{{ \App\Models\Note::MAX_BODY_LENGTH }}"
                    ></textarea>
                    @error('newNoteBody')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-end">
                        <span x-data="{ count: $wire.entangle('newNoteBody').live }" x-text="(count?.length || 0) + ' / {{ \App\Models\Note::MAX_BODY_LENGTH }}'"></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createNote">Guardar nota</span>
                    <span wire:loading wire:target="createNote">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Guardando...
                    </span>
                </button>
            </form>
        @endif

        {{-- Notes list --}}
        @if ($notes->isEmpty())
            <p class="text-muted mb-0">Sin notas.</p>
        @else
            <div class="list-group list-group-flush">
                @foreach ($notes as $note)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <small class="text-muted">
                                <strong>{{ $note->author?->name ?? 'Usuario desconocido' }}</strong>
                            </small>
                            <small class="text-muted">
                                {{ $note->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <div class="note-body" style="white-space: pre-wrap;">{{ $note->body }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $notes->links() }}
            </div>
        @endif
    </div>
</div>
