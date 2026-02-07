<?php

namespace App\Support\Timeline;

/**
 * Stable event type constants and their display metadata for the timeline.
 *
 * Each type maps to a Bootstrap Icon class and a Spanish label.
 */
final class TimelineEventType
{
    // Asset movements
    public const ASSET_ASSIGN = 'movement.asset.assign';

    public const ASSET_UNASSIGN = 'movement.asset.unassign';

    public const ASSET_LOAN = 'movement.asset.loan';

    public const ASSET_RETURN = 'movement.asset.return';

    // Product quantity movements
    public const PRODUCT_QTY_IN = 'movement.product_qty.in';

    public const PRODUCT_QTY_OUT = 'movement.product_qty.out';

    // Inventory adjustments
    public const ADJUSTMENT = 'adjustment';

    // Notes
    public const NOTE_CREATE = 'note.create';

    // Attachments
    public const ATTACHMENT_UPLOAD = 'attachment.upload';

    public const ATTACHMENT_DELETE = 'attachment.delete';

    // Audit / system events
    public const AUDIT_LOCK_FORCE_RELEASE = 'system.lock.force_release';

    public const AUDIT_LOCK_FORCE_CLAIM = 'system.lock.force_claim';

    public const AUDIT_TRASH_SOFT_DELETE = 'system.trash.soft_delete';

    public const AUDIT_TRASH_RESTORE = 'system.trash.restore';

    public const AUDIT_TRASH_PURGE = 'system.trash.purge';

    public const AUDIT_GENERIC = 'system.generic';

    /**
     * Spanish labels per event type.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        self::ASSET_ASSIGN => 'Asignacion',
        self::ASSET_UNASSIGN => 'Desasignacion',
        self::ASSET_LOAN => 'Prestamo',
        self::ASSET_RETURN => 'Devolucion',
        self::PRODUCT_QTY_IN => 'Entrada',
        self::PRODUCT_QTY_OUT => 'Salida',
        self::ADJUSTMENT => 'Ajuste',
        self::NOTE_CREATE => 'Nota',
        self::ATTACHMENT_UPLOAD => 'Adjunto subido',
        self::ATTACHMENT_DELETE => 'Adjunto eliminado',
        self::AUDIT_LOCK_FORCE_RELEASE => 'Lock liberado (admin)',
        self::AUDIT_LOCK_FORCE_CLAIM => 'Lock reclamado (admin)',
        self::AUDIT_TRASH_SOFT_DELETE => 'Eliminado',
        self::AUDIT_TRASH_RESTORE => 'Restaurado',
        self::AUDIT_TRASH_PURGE => 'Purgado',
        self::AUDIT_GENERIC => 'Evento del sistema',
    ];

    /**
     * Bootstrap Icons per event type.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        self::ASSET_ASSIGN => 'bi bi-person-check',
        self::ASSET_UNASSIGN => 'bi bi-person-dash',
        self::ASSET_LOAN => 'bi bi-box-arrow-up-right',
        self::ASSET_RETURN => 'bi bi-arrow-return-left',
        self::PRODUCT_QTY_IN => 'bi bi-box-arrow-in-down',
        self::PRODUCT_QTY_OUT => 'bi bi-box-arrow-up',
        self::ADJUSTMENT => 'bi bi-sliders',
        self::NOTE_CREATE => 'bi bi-sticky',
        self::ATTACHMENT_UPLOAD => 'bi bi-paperclip',
        self::ATTACHMENT_DELETE => 'bi bi-trash',
        self::AUDIT_LOCK_FORCE_RELEASE => 'bi bi-unlock',
        self::AUDIT_LOCK_FORCE_CLAIM => 'bi bi-lock',
        self::AUDIT_TRASH_SOFT_DELETE => 'bi bi-trash',
        self::AUDIT_TRASH_RESTORE => 'bi bi-arrow-counterclockwise',
        self::AUDIT_TRASH_PURGE => 'bi bi-x-circle',
        self::AUDIT_GENERIC => 'bi bi-gear',
    ];

    /**
     * Filter categories for chips UI.
     *
     * @var array<string, list<string>>
     */
    public const FILTER_CATEGORIES = [
        'Movimientos' => [
            self::ASSET_ASSIGN,
            self::ASSET_UNASSIGN,
            self::ASSET_LOAN,
            self::ASSET_RETURN,
            self::PRODUCT_QTY_IN,
            self::PRODUCT_QTY_OUT,
        ],
        'Ajustes' => [
            self::ADJUSTMENT,
        ],
        'Notas' => [
            self::NOTE_CREATE,
        ],
        'Adjuntos' => [
            self::ATTACHMENT_UPLOAD,
            self::ATTACHMENT_DELETE,
        ],
        'Sistema' => [
            self::AUDIT_LOCK_FORCE_RELEASE,
            self::AUDIT_LOCK_FORCE_CLAIM,
            self::AUDIT_TRASH_SOFT_DELETE,
            self::AUDIT_TRASH_RESTORE,
            self::AUDIT_TRASH_PURGE,
            self::AUDIT_GENERIC,
        ],
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? 'Evento';
    }

    public static function icon(string $type): string
    {
        return self::ICONS[$type] ?? 'bi bi-circle';
    }
}
