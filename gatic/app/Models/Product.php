<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property int|null $brand_id
 * @property int|null $qty_total
 * @property int|null $assets_total
 * @property int|null $assets_unavailable
 */
class Product extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category_id',
        'brand_id',
        'qty_total',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'qty_total' => 'int',
    ];

    public static function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\\s+/u', ' ', trim($value));

        return $normalized === '' ? null : $normalized;
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeName($value),
        );
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
