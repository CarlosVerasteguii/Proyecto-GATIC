<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $identifier
 * @property string $type
 * @property int|null $supplier_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $notes
 */
class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_LEASE = 'lease';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_PURCHASE,
        self::TYPE_LEASE,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'identifier',
        'type',
        'supplier_id',
        'start_date',
        'end_date',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get type label in Spanish.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'Compra',
            self::TYPE_LEASE => 'Arrendamiento',
            default => $this->type,
        };
    }
}
