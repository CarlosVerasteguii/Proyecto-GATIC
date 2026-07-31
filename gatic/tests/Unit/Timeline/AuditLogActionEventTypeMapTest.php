<?php

declare(strict_types=1);

namespace Tests\Unit\Timeline;

use App\Models\AuditLog;
use App\Support\Timeline\AuditLogActionEventTypeMap;
use App\Support\Timeline\TimelineEventType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogActionEventTypeMapTest extends TestCase
{
    #[Test]
    public function it_maps_audit_log_actions_to_timeline_event_types(): void
    {
        $this->assertSame([
            AuditLog::ACTION_LOCK_FORCE_RELEASE => TimelineEventType::AUDIT_LOCK_FORCE_RELEASE,
            AuditLog::ACTION_LOCK_FORCE_CLAIM => TimelineEventType::AUDIT_LOCK_FORCE_CLAIM,
            AuditLog::ACTION_TRASH_SOFT_DELETE => TimelineEventType::AUDIT_TRASH_SOFT_DELETE,
            AuditLog::ACTION_TRASH_RESTORE => TimelineEventType::AUDIT_TRASH_RESTORE,
            AuditLog::ACTION_TRASH_PURGE => TimelineEventType::AUDIT_TRASH_PURGE,
            AuditLog::ACTION_ATTACHMENT_DELETE => TimelineEventType::ATTACHMENT_DELETE,
        ], AuditLogActionEventTypeMap::map());
    }

    #[Test]
    public function it_falls_back_to_audit_generic_for_unknown_actions(): void
    {
        $this->assertSame(
            TimelineEventType::AUDIT_GENERIC,
            AuditLogActionEventTypeMap::eventTypeForAction('unknown.action'),
        );
    }
}
