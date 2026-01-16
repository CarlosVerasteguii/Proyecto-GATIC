<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $employee_id
 * @property int $actor_user_id
 * @property string $direction
 * @property int $qty
 * @property int $qty_before
 * @property int $qty_after
 * @property string $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProductQuantityMovement extends Model
{
    public const DIRECTION_OUT = 'out';

    public const DIRECTION_IN = 'in';

    public const DIRECTIONS = [
        self::DIRECTION_OUT,
        self::DIRECTION_IN,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'employee_id',
        'actor_user_id',
        'direction',
        'qty',
        'qty_before',
        'qty_after',
        'note',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'int',
        'qty_before' => 'int',
        'qty_after' => 'int',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
