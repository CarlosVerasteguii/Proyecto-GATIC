<?php

namespace App\Support\Timeline;

use Carbon\Carbon as CarbonCarbon;
use Illuminate\Support\Carbon;

/**
 * Unified timeline event DTO.
 *
 * Represents a single event from any source (movements, adjustments,
 * notes, attachments, audit logs) in a normalized format.
 */
final readonly class TimelineEvent
{
    /**
     * @param  string  $type  One of TimelineEventType::* constants
     * @param  Carbon|CarbonCarbon  $occurredAt  When the event happened
     * @param  string  $source  Source table identifier for tie-breaking (e.g., 'asset_movement', 'note')
     * @param  int  $sourceId  Primary key from source table for stable ordering
     * @param  string  $title  One-line summary
     * @param  string  $summary  One–two line detail
     * @param  int|null  $actorUserId  User who performed the action (nullable for system events)
     * @param  string|null  $actorName  Display name of actor
     * @param  array<string, scalar|null>  $meta  Allowlisted non-sensitive render data
     * @param  string|null  $routeName  Optional route name for "source of truth" link
     * @param  array<string, mixed>  $routeParams  Route parameters
     */
    public function __construct(
        public string $type,
        public Carbon|CarbonCarbon $occurredAt,
        public string $source,
        public int $sourceId,
        public string $title,
        public string $summary,
        public ?int $actorUserId = null,
        public ?string $actorName = null,
        public array $meta = [],
        public ?string $routeName = null,
        public array $routeParams = [],
    ) {}

    public function label(): string
    {
        return TimelineEventType::label($this->type);
    }

    public function icon(): string
    {
        return TimelineEventType::icon($this->type);
    }

    /**
     * Stable sort key for deterministic ordering.
     */
    public function sortKey(): string
    {
        return $this->occurredAt->format('Y-m-d H:i:s.u').'|'.$this->source.'|'.sprintf('%020d', $this->sourceId);
    }
}
