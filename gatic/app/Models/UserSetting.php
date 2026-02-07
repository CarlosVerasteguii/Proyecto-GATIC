<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User-level key-value setting override.
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property mixed $value
 * @property int|null $updated_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'updated_by_user_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'json',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
