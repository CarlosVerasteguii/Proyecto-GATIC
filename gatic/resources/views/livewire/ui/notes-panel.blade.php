@php($notes = $this->notes)

<x-ui.section-card
    title="Notas"
    subtitle="Registro manual para contexto operativo, decisiones puntuales y seguimiento."
    icon="bi-journal-text"
    bodyClass="trace-panel__body"
    class="trace-panel"
>
    <x-slot:actions>
        <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
            {{ $notes->total() }}
        </x-ui.badge>
        @unless ($this->canCreate)
            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">
                Solo lectura
            </x-ui.badge>
        @endunless
    </x-slot:actions>

    @if ($showSuccessMessage)
        <div
            x-data
            x-init="setTimeout(() => $wire.hideSuccessMessage(), 3000)"
            class="alert alert-success alert-dismissible fade show trace-panel__alert"
            role="status"
            aria-live="polite"
        >
            Nota guardada correctamente.
            <button type="button" class="btn-close" wire:click="hideSuccessMessage" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($this->canCreate)
        <form wire:submit="createNote" class="trace-panel__composer" x-data="{ noteLength: 0 }">
            <div class="trace-panel__eyebrow">Captura rápida</div>
            <h3 class="trace-panel__composer-title h6">Agregar nota</h3>
            <p class="trace-panel__hint mb-3">
                Registra contexto breve y accionable. Se almacena como texto plano para preservar seguridad y trazabilidad.
            </p>

            <div class="mb-3">
                <label for="newNoteBody" class="form-label small fw-semibold">Nueva nota</label>
                <textarea
                    id="newNoteBody"
                    name="new_note_body"
                    wire:model="newNoteBody"
                    x-ref="noteBody"
                    x-init="noteLength = $refs.noteBody.value.length"
                    x-on:input="noteLength = $event.target.value.length"
                    class="form-control @error('newNoteBody') is-invalid @enderror"
                    rows="4"
                    placeholder="Escribe una nota operativa…"
                    maxlength="{{ \App\Models\Note::MAX_BODY_LENGTH }}"
                    autocomplete="off"
                ></textarea>
                @error('newNoteBody')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="trace-panel__composer-actions">
                <div class="trace-panel__metric">
                    <span x-text="noteLength"></span> / {{ \App\Models\Note::MAX_BODY_LENGTH }}
                </div>

                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="createNote">
                    <span wire:loading.remove wire:target="createNote">Guardar nota</span>
                    <span wire:loading wire:target="createNote">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Guardando…
                    </span>
                </button>
            </div>
        </form>
    @endif

    @if ($notes->isEmpty())
        <div class="trace-panel__empty">
            <x-ui.empty-state
                icon="bi-journal-text"
                title="Sin notas"
                description="Todavía no hay notas operativas registradas para este detalle."
                compact
            />
        </div>
    @else
        <div class="trace-panel__list">
            @foreach ($notes as $note)
                <article class="trace-note" wire:key="note-{{ $note->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                        <div class="min-w-0">
                            <div class="trace-note__author">{{ $note->author?->name ?? 'Usuario desconocido' }}</div>
                            <div class="trace-note__meta">Nota manual registrada en la ficha actual.</div>
                        </div>
                        <div class="trace-note__timestamp text-end text-nowrap">
                            <div>{{ $note->created_at->format('d/m/Y H:i') }}</div>
                            <div>{{ $note->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    <div class="trace-note__body">{{ $note->body }}</div>
                </article>
            @endforeach
        </div>

        <div class="trace-panel__pagination">
            {{ $notes->links() }}
        </div>
    @endif
</x-ui.section-card>
