<?php

namespace App\Support\Timeline;

use App\Models\AuditLog;

/**
 * Shared mapping between AuditLog actions and timeline event types.
 *
 * This exists to avoid drift between per-entity timeline and dashboard recent activity feeds.
 */
final class AuditLogActionEventTypeMap
{
    /**
     * @return array<string, string> key: AuditLog::ACTION_*, value: TimelineEventType::*
     */
    public static function map(): array
    {
        return [
            AuditLog::ACTION_LOCK_FORCE_RELEASE => TimelineEventType::AUDIT_LOCK_FORCE_RELEASE,
            AuditLog::ACTION_LOCK_FORCE_CLAIM => TimelineEventType::AUDIT_LOCK_FORCE_CLAIM,
            AuditLog::ACTION_TRASH_SOFT_DELETE => TimelineEventType::AUDIT_TRASH_SOFT_DELETE,
            AuditLog::ACTION_TRASH_RESTORE => TimelineEventType::AUDIT_TRASH_RESTORE,
            AuditLog::ACTION_TRASH_PURGE => TimelineEventType::AUDIT_TRASH_PURGE,
            AuditLog::ACTION_ATTACHMENT_DELETE => TimelineEventType::ATTACHMENT_DELETE,
        ];
    }

    public static function eventTypeForAction(string $action): string
    {
        return self::map()[$action] ?? TimelineEventType::AUDIT_GENERIC;
    }
}
