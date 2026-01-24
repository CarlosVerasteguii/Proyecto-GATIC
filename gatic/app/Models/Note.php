<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Manual note attached to noteable entities (Product, Asset, Employee).
 *
 * @property int $id
 * @property string $noteable_type
 * @property int $noteable_id
 * @property int $author_user_id
 * @property string $body
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Note extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_user_id',
        'body',
    ];

    /**
     * Maximum allowed body length (AC4: validation).
     */
    public const MAX_BODY_LENGTH = 5000;

    /**
     * Get the parent noteable model (Product, Asset, Employee).
     *
     * @return MorphTo<Model, $this>
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who authored this note.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
