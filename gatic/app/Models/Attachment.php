<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * File attachment for attachable entities (Product, Asset, Employee).
 *
 * Files are stored in private storage with UUID-based paths.
 * Original filenames are preserved for UI display only.
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property int $uploaded_by_user_id
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Attachment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'uploaded_by_user_id',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * Maximum file size in bytes (10 MB).
     */
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

    /**
     * Maximum file size in KB (for Livewire validation).
     */
    public const MAX_FILE_SIZE_KB = 10 * 1024;

    /**
     * Allowed MIME types for uploads.
     *
     * @var list<string>
     */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'text/plain',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Allowed file extensions (for display/validation).
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf',
        'png',
        'jpg',
        'jpeg',
        'webp',
        'txt',
        'docx',
        'xlsx',
    ];

    /**
     * Get the parent attachable model (Product, Asset, Employee).
     *
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this attachment.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' bytes';
    }
}
