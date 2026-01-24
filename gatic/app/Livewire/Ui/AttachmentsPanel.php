<?php

namespace App\Livewire\Ui;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Product;
use App\Support\Audit\AuditRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Reusable attachments panel for entity detail pages.
 *
 * Shows existing attachments with pagination and allows Admin/Editor to upload/delete.
 * View permission requires both entity visibility AND attachments.view gate.
 */
class AttachmentsPanel extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    private const PAGINATOR_NAME = 'attachmentsPage';

    /**
     * Entity visibility gates by entity type.
     *
     * @var array<class-string<Model>, string>
     */
    private const VIEW_GATES = [
        Product::class => 'inventory.view',
        Asset::class => 'inventory.view',
        Employee::class => 'inventory.manage',
    ];

    #[Locked]
    public string $attachableType = '';

    #[Locked]
    public int $attachableId = 0;

    #[Locked]
    public string $viewGate = 'inventory.view';

    /** @var TemporaryUploadedFile|null */
    public $newFile = null;

    public bool $showSuccessMessage = false;

    public string $successMessage = '';

    public bool $showErrorMessage = false;

    public string $errorMessage = '';

    /**
     * Mount the component with the attachable entity.
     *
     * @param  string  $attachableType  The model class (e.g., Product::class)
     * @param  int  $attachableId  The entity ID
     */
    public function mount(string $attachableType, int $attachableId): void
    {
        if (! array_key_exists($attachableType, self::VIEW_GATES)) {
            abort(404);
        }

        if ($attachableId <= 0) {
            abort(404);
        }

        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
        $this->viewGate = self::VIEW_GATES[$attachableType];

        // Require both entity visibility AND attachments.view
        Gate::authorize($this->viewGate);
        Gate::authorize('attachments.view');
    }

    /**
     * Get paginated attachments for the entity.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Attachment>
     */
    #[Computed]
    public function attachments(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Attachment::query()
            ->where('attachable_type', $this->attachableType)
            ->where('attachable_id', $this->attachableId)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], self::PAGINATOR_NAME);
    }

    /**
     * Check if current user can manage attachments (upload/delete).
     */
    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('attachments.manage');
    }

    /**
     * Upload a new attachment (Admin/Editor only).
     */
    public function uploadAttachment(): void
    {
        Gate::authorize($this->viewGate);
        Gate::authorize('attachments.manage');

        $this->resetMessages();

        $this->validate([
            'newFile' => [
                'required',
                'file',
                'max:'.Attachment::MAX_FILE_SIZE_KB,
                'mimes:'.implode(',', Attachment::ALLOWED_EXTENSIONS),
            ],
        ], [
            'newFile.required' => 'Selecciona un archivo para subir.',
            'newFile.file' => 'El archivo no es válido.',
            'newFile.max' => 'El archivo no puede exceder 10 MB.',
            'newFile.mimes' => 'Tipo de archivo no permitido. Permitidos: PDF, imágenes (PNG, JPG, WEBP), texto, Word, Excel.',
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $this->newFile;

        // Extra server-side MIME validation (defense in depth)
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, Attachment::ALLOWED_MIME_TYPES, true)) {
            $this->addError('newFile', 'Tipo de archivo no permitido.');

            return;
        }

        $storedPath = null;

        try {
            $attachable = $this->resolveAttachable();

            // Generate UUID-based safe filename
            $uuid = (string) Str::uuid();
            $typeFolder = class_basename($this->attachableType);
            $storagePath = "attachments/{$typeFolder}/{$this->attachableId}/{$uuid}";

            // Store file in private disk
            $storedPath = $file->storeAs(
                dirname($storagePath),
                basename($storagePath),
                'local'
            );

            if ($storedPath === false) {
                $this->showError('Error al guardar el archivo. Intenta de nuevo.');

                return;
            }

            // Create attachment record
            /** @var Product|Asset|Employee $attachable */
            $attachment = $attachable->attachments()->create([
                'uploaded_by_user_id' => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $storedPath,
                'mime_type' => $mimeType,
                'size_bytes' => $file->getSize(),
            ]);

            // Audit best-effort (AC6)
            $this->recordUploadAudit($attachment);

            $this->newFile = null;
            $this->showSuccess('Archivo subido correctamente.');
            $this->resetPage(self::PAGINATOR_NAME);

        } catch (\Throwable $e) {
            if (is_string($storedPath) && $storedPath !== '') {
                try {
                    $deleted = Storage::disk('local')->delete($storedPath);
                    if (! $deleted) {
                        Log::warning('Attachment upload cleanup failed (delete returned false)', [
                            'attachable_type' => $this->attachableType,
                            'attachable_id' => $this->attachableId,
                            'stored_path' => $storedPath,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::warning('Attachment upload cleanup failed', [
                        'attachable_type' => $this->attachableType,
                        'attachable_id' => $this->attachableId,
                        'stored_path' => $storedPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            Log::error('Attachment upload failed', [
                'attachable_type' => $this->attachableType,
                'attachable_id' => $this->attachableId,
                'error' => $e->getMessage(),
            ]);

            $this->showError('Error al subir el archivo. Intenta de nuevo.');
        }
    }

    /**
     * Delete an attachment (Admin/Editor only).
     */
    public function deleteAttachment(int $attachmentId): void
    {
        Gate::authorize($this->viewGate);
        Gate::authorize('attachments.manage');

        $this->resetMessages();

        $attachment = Attachment::query()
            ->where('id', $attachmentId)
            ->where('attachable_type', $this->attachableType)
            ->where('attachable_id', $this->attachableId)
            ->first();

        if (! $attachment) {
            $this->showError('Adjunto no encontrado.');

            return;
        }

        try {
            // Store info for audit before deletion
            $attachmentIdForAudit = $attachment->id;
            $originalName = $attachment->original_name;
            $path = $attachment->path;
            $disk = $attachment->disk;

            // Delete the database record
            $attachment->delete();

            // Delete file from storage (graceful: allow cleanup even if file missing)
            try {
                $deleted = Storage::disk($disk)->delete($path);
                if (! $deleted) {
                    Log::warning('Attachment file delete returned false', [
                        'attachment_id' => $attachmentIdForAudit,
                        'disk' => $disk,
                        'path' => $path,
                    ]);
                }
            } catch (\Throwable $deleteException) {
                Log::warning('Attachment file delete failed', [
                    'attachment_id' => $attachmentIdForAudit,
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $deleteException->getMessage(),
                ]);
            }

            // Audit best-effort (AC6)
            $this->recordDeleteAudit($attachmentIdForAudit, $originalName);

            $this->showSuccess('Adjunto eliminado correctamente.');

        } catch (\Throwable $e) {
            Log::error('Attachment delete failed', [
                'attachment_id' => $attachmentId,
                'attachable_type' => $this->attachableType,
                'attachable_id' => $this->attachableId,
                'error' => $e->getMessage(),
            ]);

            $this->showError('Error al eliminar el adjunto. Intenta de nuevo.');
        }
    }

    /**
     * Resolve the attachable entity safely.
     */
    private function resolveAttachable(): Model
    {
        /** @var class-string<Model> $attachableType */
        $attachableType = $this->attachableType;

        /** @var Model $attachable */
        $attachable = $attachableType::query()->findOrFail($this->attachableId);

        if (! method_exists($attachable, 'attachments')) {
            abort(500);
        }

        return $attachable;
    }

    /**
     * Record audit event for upload (best-effort, non-blocking).
     */
    private function recordUploadAudit(Attachment $attachment): void
    {
        $context = [
            'attachment_id' => $attachment->id,
            'summary' => Str::limit($attachment->original_name, 80),
        ];

        // Add entity-specific ID to context
        $entityIdKey = $this->getEntityIdKey();
        if ($entityIdKey !== null) {
            $context[$entityIdKey] = $this->attachableId;
        }

        AuditRecorder::record(
            action: AuditLog::ACTION_ATTACHMENT_UPLOAD,
            subjectType: $this->attachableType,
            subjectId: $this->attachableId,
            actorUserId: auth()->id(),
            context: $context
        );
    }

    /**
     * Record audit event for deletion (best-effort, non-blocking).
     */
    private function recordDeleteAudit(int $attachmentId, string $originalName): void
    {
        $context = [
            'attachment_id' => $attachmentId,
            'summary' => Str::limit($originalName, 80),
        ];

        // Add entity-specific ID to context
        $entityIdKey = $this->getEntityIdKey();
        if ($entityIdKey !== null) {
            $context[$entityIdKey] = $this->attachableId;
        }

        AuditRecorder::record(
            action: AuditLog::ACTION_ATTACHMENT_DELETE,
            subjectType: $this->attachableType,
            subjectId: $this->attachableId,
            actorUserId: auth()->id(),
            context: $context
        );
    }

    /**
     * Get the entity ID key for audit context.
     */
    private function getEntityIdKey(): ?string
    {
        return match ($this->attachableType) {
            Product::class => 'product_id',
            Asset::class => 'asset_id',
            Employee::class => 'employee_id',
            default => null,
        };
    }

    /**
     * Show success message.
     */
    private function showSuccess(string $message): void
    {
        $this->showSuccessMessage = true;
        $this->successMessage = $message;
    }

    /**
     * Show error message.
     */
    private function showError(string $message): void
    {
        $this->showErrorMessage = true;
        $this->errorMessage = $message;
    }

    /**
     * Reset all messages.
     */
    private function resetMessages(): void
    {
        $this->showSuccessMessage = false;
        $this->successMessage = '';
        $this->showErrorMessage = false;
        $this->errorMessage = '';
    }

    /**
     * Hide the success message.
     */
    public function hideSuccessMessage(): void
    {
        $this->showSuccessMessage = false;
    }

    /**
     * Hide the error message.
     */
    public function hideErrorMessage(): void
    {
        $this->showErrorMessage = false;
    }

    public function render(): View
    {
        Gate::authorize($this->viewGate);
        Gate::authorize('attachments.view');

        return view('livewire.ui.attachments-panel');
    }
}
