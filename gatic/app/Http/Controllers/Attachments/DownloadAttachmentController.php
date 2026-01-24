<?php

namespace App\Http\Controllers\Attachments;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Employee;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for secure attachment downloads.
 *
 * This is a "border" controller that handles file streaming with proper
 * authorization checks. All attachments must be downloaded through this
 * endpoint to enforce access control (AC3, AC5).
 */
class DownloadAttachmentController extends Controller
{
    /**
     * Entity visibility gates by entity type.
     *
     * @var array<class-string, string>
     */
    private const VIEW_GATES = [
        Product::class => 'inventory.view',
        Asset::class => 'inventory.view',
        Employee::class => 'inventory.manage',
    ];

    /**
     * Download an attachment.
     */
    public function __invoke(Request $request, int $id): StreamedResponse
    {
        // First check if user can view attachments at all
        Gate::authorize('attachments.view');

        // Find the attachment
        $attachment = Attachment::query()->find($id);

        if (! $attachment) {
            abort(404);
        }

        // Check if user can view the parent entity
        $entityGate = self::VIEW_GATES[$attachment->attachable_type] ?? null;

        if ($entityGate === null) {
            abort(404);
        }

        Gate::authorize($entityGate);

        // Verify the parent entity exists and is not soft-deleted
        $attachableExists = $this->attachableExists($attachment);

        if (! $attachableExists) {
            abort(404);
        }

        // Verify file exists in storage
        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            // File missing from storage - log warning and show 404
            Log::warning('Attachment file missing from storage', [
                'attachment_id' => $attachment->id,
            ]);

            abort(404);
        }

        // Stream the file with proper Content-Disposition header
        return $disk->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
            ]
        );
    }

    /**
     * Check if the attachable entity exists (and is not soft-deleted).
     */
    private function attachableExists(Attachment $attachment): bool
    {
        /** @var class-string $attachableType */
        $attachableType = $attachment->attachable_type;

        // SoftDeletes models should only show attachments for non-deleted records
        return $attachableType::query()
            ->where('id', $attachment->attachable_id)
            ->exists();
    }
}
