<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $actor_user_id
 * @property string $reason
 */
class InventoryAdjustment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_user_id',
        'reason',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return HasMany<InventoryAdjustmentEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentEntry::class);
    }
}
