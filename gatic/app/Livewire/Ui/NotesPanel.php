<?php

namespace App\Livewire\Ui;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Note;
use App\Models\Product;
use App\Support\Audit\AuditRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reusable notes panel for entity detail pages.
 *
 * Shows existing notes with pagination and allows Admin/Editor to create new notes.
 * View permission is inherited from entity visibility (inventory.view for Product/Asset,
 * inventory.manage for Employee).
 */
class NotesPanel extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    private const PAGINATOR_NAME = 'notesPage';

    /**
     * Notes visibility gates by entity type.
     *
     * @var array<class-string<Model>, string>
     */
    private const VIEW_GATES = [
        Product::class => 'inventory.view',
        Asset::class => 'inventory.view',
        Employee::class => 'inventory.manage',
    ];

    #[Locked]
    public string $noteableType = '';

    #[Locked]
    public int $noteableId = 0;

    #[Locked]
    public string $viewGate = 'inventory.view';

    public string $newNoteBody = '';

    public bool $showSuccessMessage = false;

    /**
     * Mount the component with the noteable entity.
     *
     * @param  string  $noteableType  The model class (e.g., Product::class)
     * @param  int  $noteableId  The entity ID
     */
    public function mount(string $noteableType, int $noteableId): void
    {
        if (! array_key_exists($noteableType, self::VIEW_GATES)) {
            abort(404);
        }

        if ($noteableId <= 0) {
            abort(404);
        }

        $this->noteableType = $noteableType;
        $this->noteableId = $noteableId;
        $this->viewGate = self::VIEW_GATES[$noteableType];

        Gate::authorize($this->viewGate);
    }

    /**
     * Get paginated notes for the entity.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Note>
     */
    #[Computed]
    public function notes(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Note::query()
            ->where('noteable_type', $this->noteableType)
            ->where('noteable_id', $this->noteableId)
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], self::PAGINATOR_NAME);
    }

    /**
     * Check if current user can create notes.
     */
    #[Computed]
    public function canCreate(): bool
    {
        return Gate::allows('notes.manage');
    }

    /**
     * Create a new note (Admin/Editor only).
     */
    public function createNote(): void
    {
        Gate::authorize($this->viewGate);
        Gate::authorize('notes.manage');

        $validated = $this->validate([
            'newNoteBody' => ['required', 'string', 'max:'.Note::MAX_BODY_LENGTH],
        ], [
            'newNoteBody.required' => 'El contenido de la nota es requerido.',
            'newNoteBody.max' => 'La nota no puede exceder '.Note::MAX_BODY_LENGTH.' caracteres.',
        ]);

        // Sanitize: trim and store as plain text (no HTML)
        $body = trim(strip_tags($validated['newNoteBody']));

        if ($body === '') {
            $this->addError('newNoteBody', 'El contenido de la nota es requerido.');

            return;
        }

        $noteable = $this->resolveNoteable();

        $note = $noteable->notes()->create([
            'author_user_id' => auth()->id(),
            'body' => $body,
        ]);

        // Audit best-effort (AC5)
        $this->recordAudit($note);

        $this->newNoteBody = '';
        $this->showSuccessMessage = true;
        $this->resetPage(self::PAGINATOR_NAME);

        // Auto-hide success message after 3 seconds via JS
    }

    /**
     * Resolve the noteable entity for safe note creation.
     */
    private function resolveNoteable(): Product|Asset|Employee
    {
        return match ($this->noteableType) {
            Product::class => Product::query()->findOrFail($this->noteableId),
            Asset::class => Asset::query()->findOrFail($this->noteableId),
            Employee::class => Employee::query()->findOrFail($this->noteableId),
            default => abort(404),
        };
    }

    /**
     * Record audit event for note creation (best-effort, non-blocking).
     */
    private function recordAudit(Note $note): void
    {
        AuditRecorder::record(
            action: AuditLog::ACTION_NOTE_MANUAL_CREATE,
            subjectType: $this->noteableType,
            subjectId: $this->noteableId,
            actorUserId: auth()->id(),
            context: [
                'note_id' => $note->id,
                'summary' => Str::limit($note->body, 80),
            ]
        );
    }

    /**
     * Hide the success message.
     */
    public function hideSuccessMessage(): void
    {
        $this->showSuccessMessage = false;
    }

    public function render(): View
    {
        Gate::authorize($this->viewGate);

        return view('livewire.ui.notes-panel');
    }
}
