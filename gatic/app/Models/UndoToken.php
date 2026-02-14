<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $actor_user_id
 * @property string $movement_kind
 * @property int|null $movement_id
 * @property string|null $batch_uuid
 * @property \Illuminate\Support\CarbonImmutable $expires_at
 * @property \Illuminate\Support\CarbonImmutable|null $used_at
 * @property int|null $used_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UndoToken extends Model
{
    use HasFactory;
    use Prunable;

    public const KIND_ASSET_MOVEMENT = 'asset_movement';

    public const KIND_PRODUCT_QTY_MOVEMENT = 'product_qty_movement';

    /**
     * @var list<string>
     */
    public const KINDS = [
        self::KIND_ASSET_MOVEMENT,
        self::KIND_PRODUCT_QTY_MOVEMENT,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'actor_user_id',
        'movement_kind',
        'movement_id',
        'batch_uuid',
        'expires_at',
        'used_at',
        'used_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $retentionDays = (int) config('gatic.inventory.undo.token_retention_days', 7);
        $retentionDays = $retentionDays > 0 ? $retentionDays : 7;

        $cutoff = now()->subDays($retentionDays);

        return static::query()
            ->whereNotNull('used_at')
            ->where('used_at', '<', $cutoff)
            ->orWhere('expires_at', '<', $cutoff);
    }
}
