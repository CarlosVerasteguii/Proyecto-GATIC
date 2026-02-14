<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asset_id
 * @property int $employee_id
 * @property int $actor_user_id
 * @property string|null $batch_uuid
 * @property string $type
 * @property string $note
 * @property \Illuminate\Support\CarbonImmutable|null $loan_due_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AssetMovement extends Model
{
    use HasFactory;

    public const TYPE_ASSIGN = 'assign';

    public const TYPE_UNASSIGN = 'unassign';

    public const TYPE_LOAN = 'loan';

    public const TYPE_RETURN = 'return';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_ASSIGN,
        self::TYPE_UNASSIGN,
        self::TYPE_LOAN,
        self::TYPE_RETURN,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asset_id',
        'employee_id',
        'actor_user_id',
        'batch_uuid',
        'type',
        'loan_due_date',
        'note',
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

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
