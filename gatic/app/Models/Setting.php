<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * System-level key-value setting override.
 *
 * Settings stored here override the defaults defined in config/gatic.php.
 * When a key is absent from this table, the system falls back to config().
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property int|null $updated_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    /** @var list<string> */
    protected $fillable = [
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
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
