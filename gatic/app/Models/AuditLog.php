<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cross-cutting audit log entry.
 *
 * Stores "what happened, who did it, to what entity, when" with minimal context.
 * This is a transversal feed, NOT a replacement for domain-specific histories
 * (e.g., AssetMovement, ProductQuantityMovement, InventoryAdjustment).
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property int|null $actor_user_id
 * @property string $action
 * @property string $subject_type
 * @property int $subject_id
 * @property array<string, mixed>|null $context
 */
class AuditLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     * We only use created_at (no updated_at for audit immutability).
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_at',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'context',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'context' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Action constants (stable strings for filtering and display)
    // ──────────────────────────────────────────────────────────────────────────

    // Lock overrides (Story 7.5)
    public const ACTION_LOCK_FORCE_RELEASE = 'pending_tasks.lock.force_release';

    public const ACTION_LOCK_FORCE_CLAIM = 'pending_tasks.lock.force_claim';

    // Inventory adjustments (FR14)
    public const ACTION_INVENTORY_ADJUSTMENT = 'inventory.adjustment.apply';

    // Asset movements (FR17–FR22)
    public const ACTION_ASSET_ASSIGN = 'movements.asset.assign';

    public const ACTION_ASSET_LOAN = 'movements.asset.loan';

    public const ACTION_ASSET_RETURN = 'movements.asset.return';

    // Product quantity movements
    public const ACTION_PRODUCT_QTY_REGISTER = 'movements.product_qty.register';

    // Manual notes (Story 8.2)
    public const ACTION_NOTE_MANUAL_CREATE = 'notes.manual.create';

    // Attachments (Story 8.3)
    public const ACTION_ATTACHMENT_UPLOAD = 'attachments.upload';

    public const ACTION_ATTACHMENT_DELETE = 'attachments.delete';

    // Trash operations (Story 8.4)
    public const ACTION_TRASH_SOFT_DELETE = 'trash.soft_delete';

    public const ACTION_TRASH_RESTORE = 'trash.restore';

    public const ACTION_TRASH_PURGE = 'trash.purge';

    /**
     * All defined actions for filtering/validation.
     *
     * @var list<string>
     */
    public const ACTIONS = [
        self::ACTION_LOCK_FORCE_RELEASE,
        self::ACTION_LOCK_FORCE_CLAIM,
        self::ACTION_INVENTORY_ADJUSTMENT,
        self::ACTION_ASSET_ASSIGN,
        self::ACTION_ASSET_LOAN,
        self::ACTION_ASSET_RETURN,
        self::ACTION_PRODUCT_QTY_REGISTER,
        self::ACTION_NOTE_MANUAL_CREATE,
        self::ACTION_ATTACHMENT_UPLOAD,
        self::ACTION_ATTACHMENT_DELETE,
        self::ACTION_TRASH_SOFT_DELETE,
        self::ACTION_TRASH_RESTORE,
        self::ACTION_TRASH_PURGE,
    ];

    /**
     * Human-readable labels for actions (Spanish).
     *
     * @var array<string, string>
     */
    public const ACTION_LABELS = [
        self::ACTION_LOCK_FORCE_RELEASE => 'Lock liberado (admin)',
        self::ACTION_LOCK_FORCE_CLAIM => 'Lock reclamado (admin)',
        self::ACTION_INVENTORY_ADJUSTMENT => 'Ajuste de inventario',
        self::ACTION_ASSET_ASSIGN => 'Asignación de activo',
        self::ACTION_ASSET_LOAN => 'Préstamo de activo',
        self::ACTION_ASSET_RETURN => 'Devolución de activo',
        self::ACTION_PRODUCT_QTY_REGISTER => 'Movimiento de cantidad',
        self::ACTION_NOTE_MANUAL_CREATE => 'Nota manual creada',
        self::ACTION_ATTACHMENT_UPLOAD => 'Adjunto subido',
        self::ACTION_ATTACHMENT_DELETE => 'Adjunto eliminado',
        self::ACTION_TRASH_SOFT_DELETE => 'Registro eliminado',
        self::ACTION_TRASH_RESTORE => 'Registro restaurado',
        self::ACTION_TRASH_PURGE => 'Registro purgado',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * Get short subject type (class basename).
     */
    public function getSubjectTypeShortAttribute(): string
    {
        return class_basename($this->subject_type);
    }
}
