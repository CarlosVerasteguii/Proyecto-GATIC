<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_adjustment_id
 * @property string $subject_type
 * @property int $subject_id
 * @property int|null $product_id
 * @property int|null $asset_id
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 */
class InventoryAdjustmentEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_adjustment_id',
        'subject_type',
        'subject_id',
        'product_id',
        'asset_id',
        'before',
        'after',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    /**
     * @return BelongsTo<InventoryAdjustment, $this>
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
