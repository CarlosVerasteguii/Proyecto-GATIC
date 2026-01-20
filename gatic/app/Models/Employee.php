<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $rpe
 * @property string $name
 * @property string|null $department
 * @property string|null $job_title
 * @property-read string $full_name
 */
class Employee extends Model
{
    use HasFactory;

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

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->rpe} - {$this->name}",
        );
    }

    /**
     * Assets currently assigned to this employee.
     *
     * @return HasMany<Asset, $this>
     */
    public function assignedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'current_employee_id')
            ->where('status', Asset::STATUS_ASSIGNED);
    }

    /**
     * Assets currently loaned to this employee.
     *
     * @return HasMany<Asset, $this>
     */
    public function loanedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'current_employee_id')
            ->where('status', Asset::STATUS_LOANED);
    }

    /**
     * All movement records involving this employee.
     *
     * @return HasMany<AssetMovement, $this>
     */
    public function assetMovements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }
}
