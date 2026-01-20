<?php

namespace App\Models;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pending_task_id
 * @property PendingTaskLineType $line_type
 * @property int $product_id
 * @property string|null $serial
 * @property string|null $asset_tag
 * @property int|null $quantity
 * @property int $employee_id
 * @property string $note
 * @property PendingTaskLineStatus $line_status
 * @property string|null $error_message
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read PendingTask $pendingTask
 * @property-read Product $product
 * @property-read Employee $employee
 */
class PendingTaskLine extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pending_task_id',
        'line_type',
        'product_id',
        'serial',
        'asset_tag',
        'quantity',
        'employee_id',
        'note',
        'line_status',
        'error_message',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_type' => PendingTaskLineType::class,
            'line_status' => PendingTaskLineStatus::class,
            'quantity' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Normalize serial on set (consistent with Asset model)
     */
    protected function serial(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => Asset::normalizeSerial($value),
        );
    }

    /**
     * Normalize asset_tag on set (consistent with Asset model)
     */
    protected function assetTag(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => Asset::normalizeAssetTag($value),
        );
    }

    /**
     * @return BelongsTo<PendingTask, $this>
     */
    public function pendingTask(): BelongsTo
    {
        return $this->belongsTo(PendingTask::class);
    }

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
     * Check if this is a serialized line
     */
    public function isSerialized(): bool
    {
        return $this->line_type === PendingTaskLineType::Serialized;
    }

    /**
     * Check if this is a quantity line
     */
    public function isQuantity(): bool
    {
        return $this->line_type === PendingTaskLineType::Quantity;
    }

    /**
     * Get display value for serial/asset_tag or quantity
     */
    public function getIdentifierDisplayAttribute(): string
    {
        if ($this->isSerialized()) {
            $parts = [];
            if ($this->serial) {
                $parts[] = "S/N: {$this->serial}";
            }
            if ($this->asset_tag) {
                $parts[] = "Tag: {$this->asset_tag}";
            }

            return implode(' | ', $parts) ?: 'Sin identificador';
        }

        return "Cantidad: {$this->quantity}";
    }
}
