<?php

namespace App\Livewire\Ui;

use App\Support\Timeline\TimelineBuilder;
use App\Support\Timeline\TimelineEvent;
use App\Support\Timeline\TimelineEventType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Reusable timeline panel for entity detail pages.
 *
 * Shows a unified chronological feed of movements, adjustments,
 * notes, attachments, and audit events for the given entity.
 */
class TimelinePanel extends Component
{
    #[Locked]
    public string $entityType = '';

    #[Locked]
    public int $entityId = 0;

    #[Locked]
    public string $viewGate = 'inventory.view';

    /** @var list<string> */
    public array $activeFilters = [];

    /** @var list<array<string, mixed>> */
    public array $events = [];

    public bool $hasMore = false;

    #[Locked]
    public ?string $cursorTimestamp = null;

    #[Locked]
    public ?string $cursorSortKey = null;

    private const PAGE_SIZE = 25;

    public function mount(string $entityType, int $entityId): void
    {
        if (! array_key_exists($entityType, TimelineBuilder::ALLOWED_ENTITIES)) {
            abort(404);
        }

        if ($entityId <= 0) {
            abort(404);
        }

        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->viewGate = TimelineBuilder::VIEW_GATES[$entityType];

        Gate::authorize($this->viewGate);

        $this->loadEvents();
    }

    /**
     * Toggle a filter category on/off.
     */
    public function toggleFilter(string $category): void
    {
        Gate::authorize($this->viewGate);

        if (in_array($category, $this->activeFilters, true)) {
            $this->activeFilters = array_values(
                array_filter($this->activeFilters, static fn (string $f): bool => $f !== $category)
            );
        } else {
            $this->activeFilters[] = $category;
        }

        // Reset pagination when filters change
        $this->events = [];
        $this->cursorTimestamp = null;
        $this->cursorSortKey = null;
        $this->hasMore = false;

        $this->loadEvents();
    }

    /**
     * Load more events (pagination).
     */
    public function loadMore(): void
    {
        Gate::authorize($this->viewGate);
        $this->loadEvents();
    }

    /**
     * Get available filter categories for the current entity.
     *
     * @return list<string>
     */
    public function getAvailableFiltersProperty(): array
    {
        $categories = array_keys(TimelineEventType::FILTER_CATEGORIES);

        // Remove "Adjuntos" if user can't view attachments
        if (! Gate::allows('attachments.view')) {
            $categories = array_filter($categories, static fn (string $c): bool => $c !== 'Adjuntos');
        }

        return array_values($categories);
    }

    public function render(): View
    {
        Gate::authorize($this->viewGate);

        return view('livewire.ui.timeline-panel');
    }

    private function loadEvents(): void
    {
        $cursor = null;
        if ($this->cursorTimestamp !== null) {
            try {
                $cursor = Carbon::parse($this->cursorTimestamp);
            } catch (\Throwable) {
                $cursor = null;
                $this->cursorTimestamp = null;
                $this->cursorSortKey = null;
            }
        }

        $filters = $this->activeFilters !== [] ? $this->activeFilters : null;

        $builder = new TimelineBuilder(
            entityType: $this->entityType,
            entityId: $this->entityId,
            canViewAttachments: Gate::allows('attachments.view'),
            filterCategories: $filters,
            beforeCursor: $cursor,
            beforeSortKey: $this->cursorSortKey,
            pageSize: self::PAGE_SIZE + 1, // fetch one extra to check hasMore
        );

        $newEvents = $builder->build();

        // Check if there are more results
        if (count($newEvents) > self::PAGE_SIZE) {
            $this->hasMore = true;
            $newEvents = array_slice($newEvents, 0, self::PAGE_SIZE);
        } else {
            $this->hasMore = false;
        }

        // Serialize events for Livewire state
        $serialized = array_map(static fn (TimelineEvent $e): array => [
            'type' => $e->type,
            'occurred_at' => $e->occurredAt->toIso8601String(),
            'occurred_at_human' => $e->occurredAt->format('d/m/Y H:i'),
            'occurred_at_diff' => $e->occurredAt->diffForHumans(),
            'source' => $e->source,
            'source_id' => $e->sourceId,
            'title' => $e->title,
            'summary' => $e->summary,
            'actor_name' => $e->actorName,
            'label' => $e->label(),
            'icon' => $e->icon(),
            'meta' => $e->meta,
            'route_url' => $e->routeName !== null ? route($e->routeName, $e->routeParams) : null,
        ], $newEvents);

        $this->events = array_merge($this->events, $serialized);

        // Update cursor to last event
        if ($newEvents !== []) {
            $lastEvent = end($newEvents);
            $this->cursorTimestamp = $lastEvent->occurredAt->toIso8601String();
            $this->cursorSortKey = $lastEvent->sortKey();
        }
    }
}
