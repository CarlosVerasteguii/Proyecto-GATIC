<?php

namespace App\Models;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property PendingTaskType $type
 * @property string|null $description
 * @property array<string, mixed>|null $payload
 * @property PendingTaskStatus $status
 * @property int $creator_user_id
 * @property int|null $locked_by_user_id
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $heartbeat_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $creator
 * @property-read User|null $lockedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PendingTaskLine> $lines
 */
class PendingTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const QUICK_CAPTURE_SCHEMA = 'fp03.quick_capture';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'description',
        'payload',
        'status',
        'creator_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PendingTaskType::class,
            'status' => PendingTaskStatus::class,
            'payload' => 'array',
            'locked_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    /**
     * @return HasMany<PendingTaskLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PendingTaskLine::class)->orderBy('order');
    }

    /**
     * Check if task is in draft state and allows editing
     */
    public function isDraft(): bool
    {
        return $this->status === PendingTaskStatus::Draft;
    }

    /**
     * Check if task allows adding/editing/removing lines
     */
    public function allowsLineEditing(): bool
    {
        return $this->status->allowsLineEditing();
    }

    public function isQuickCaptureTask(): bool
    {
        return is_array($this->payload)
            && ($this->payload['schema'] ?? null) === self::QUICK_CAPTURE_SCHEMA;
    }

    /**
     * Get the count of lines in this task
     */
    public function getLinesCountAttribute(): int
    {
        // ⚠️ N+1 WARNING: This triggers a query for every task if accessed directly.
        // Use withCount('lines') and access $task->lines_count instead when possible.
        return $this->lines()->count();
    }

    /**
     * Check if the task has an active lock (not expired)
     */
    public function hasActiveLock(): bool
    {
        return $this->locked_by_user_id !== null
            && $this->expires_at !== null
            && $this->expires_at->gt(now());
    }

    /**
     * Check if a specific user owns the active lock
     */
    public function isLockedBy(int $userId): bool
    {
        return $this->hasActiveLock() && $this->locked_by_user_id === $userId;
    }

    /**
     * Check if the task is locked by someone else (not the given user)
     */
    public function isLockedByOther(int $userId): bool
    {
        return $this->hasActiveLock() && $this->locked_by_user_id !== $userId;
    }

    /**
     * Get duplicated serials/asset_tags within this task for UI highlighting
     *
     * @return array<string, list<int>>
     */
    public function getDuplicateIdentifiers(): array
    {
        $serials = [];
        $assetTags = [];

        foreach ($this->lines as $line) {
            if ($line->serial !== null && $line->serial !== '') {
                $serials[$line->serial][] = $line->id;
            }
            if ($line->asset_tag !== null && $line->asset_tag !== '') {
                $assetTags[$line->asset_tag][] = $line->id;
            }
        }

        $duplicates = [];

        foreach ($serials as $serial => $lineIds) {
            if (count($lineIds) > 1) {
                $duplicates["serial:{$serial}"] = $lineIds;
            }
        }

        foreach ($assetTags as $tag => $lineIds) {
            if (count($lineIds) > 1) {
                $duplicates["asset_tag:{$tag}"] = $lineIds;
            }
        }

        return $duplicates;
    }
}
