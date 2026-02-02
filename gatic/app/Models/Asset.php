<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property int $location_id
 * @property int|null $current_employee_id
 * @property string $serial
 * @property string|null $asset_tag
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $loan_due_date
 */
class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'Disponible';

    public const STATUS_ASSIGNED = 'Asignado';

    public const STATUS_LOANED = 'Prestado';

    public const STATUS_PENDING_RETIREMENT = 'Pendiente de Retiro';

    public const STATUS_RETIRED = 'Retirado';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_ASSIGNED,
        self::STATUS_LOANED,
        self::STATUS_PENDING_RETIREMENT,
        self::STATUS_RETIRED,
    ];

    /**
     * @var list<string>
     */
    public const UNAVAILABLE_STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_LOANED,
        self::STATUS_PENDING_RETIREMENT,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'location_id',
        'current_employee_id',
        'serial',
        'asset_tag',
        'status',
        'loan_due_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_due_date' => 'immutable_date',
        ];
    }

    public static function normalizeSerial(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public static function normalizeAssetTag(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return $normalized === '' ? null : $normalized;
    }

    protected function serial(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeSerial($value),
        );
    }

    protected function assetTag(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::normalizeAssetTag($value),
        );
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function currentEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'current_employee_id');
    }

    /**
     * @return HasMany<AssetMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
