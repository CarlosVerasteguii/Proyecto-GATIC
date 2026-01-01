<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_serialized
 * @property bool $requires_asset_tag
 */
class Category extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_serialized',
        'requires_asset_tag',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_serialized' => 'bool',
        'requires_asset_tag' => 'bool',
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
}
