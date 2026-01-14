<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $rpe
 * @property string $name
 * @property string|null $department
 * @property string|null $job_title
 */
class Employee extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'rpe',
        'name',
        'department',
        'job_title',
    ];

    public static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === '' ? null : $normalized;
    }

    protected function rpe(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeText($value),
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeText($value),
        );
    }

    protected function department(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeText($value),
        );
    }

    protected function jobTitle(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeText($value),
        );
    }
}
