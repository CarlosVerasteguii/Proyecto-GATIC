<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Models\PendingTask;
use App\Support\Dashboard\PendingTaskCountsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskCountsReaderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_zeroes_when_there_are_no_pending_tasks(): void
    {
        $counts = app(PendingTaskCountsReader::class)->get();

        $this->assertSame([
            'ready' => 0,
            'processing' => 0,
            'active' => 0,
        ], $counts);
    }

    #[Test]
    public function it_counts_only_ready_and_processing_tasks(): void
    {
        PendingTask::factory()->ready()->count(2)->create();
        PendingTask::factory()->processing()->count(3)->create();
        PendingTask::factory()->draft()->create();
        PendingTask::factory()->completed()->create();

        $deletedReadyTask = PendingTask::factory()->ready()->create();
        $deletedReadyTask->delete();

        $counts = app(PendingTaskCountsReader::class)->get();

        $this->assertSame([
            'ready' => 2,
            'processing' => 3,
            'active' => 5,
        ], $counts);
    }
}
