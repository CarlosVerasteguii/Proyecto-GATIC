<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Models\PendingTaskLine;
use App\Support\PendingTasks\PendingTaskEditableLineResolver;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskEditableLineResolverTest extends TestCase
{
    #[Test]
    public function it_returns_null_for_a_missing_draft_editor_line(): void
    {
        $line = $this->resolver()->resolveDraftEditorLine(10, null);

        $this->assertNull($line);
    }

    #[Test]
    public function it_returns_null_when_the_line_does_not_belong_to_the_task(): void
    {
        $line = $this->makeLine(id: 7, pendingTaskId: 22);

        $resolved = $this->resolver([$line])->resolveDraftEditorLine(99, $line->id);

        $this->assertNull($resolved);
    }

    #[Test]
    public function it_resolves_a_draft_editor_line_owned_by_the_task(): void
    {
        $line = $this->makeLine(id: 8, pendingTaskId: 33);

        $resolved = $this->resolver([$line])->resolveDraftEditorLine(33, $line->id);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($line));
    }

    #[Test]
    public function it_blocks_applied_process_editor_lines(): void
    {
        $line = $this->makeLine(
            id: 9,
            pendingTaskId: 44,
            status: PendingTaskLineStatus::Applied,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se puede editar un renglón ya aplicado.');

        $this->resolver([$line])->resolveProcessEditorLine(44, $line->id);
    }

    #[Test]
    public function it_resolves_a_non_applied_process_editor_line(): void
    {
        $line = $this->makeLine(
            id: 10,
            pendingTaskId: 55,
            status: PendingTaskLineStatus::Pending,
        );

        $resolved = $this->resolver([$line])->resolveProcessEditorLine(55, $line->id);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($line));
    }

    /**
     * @param  list<PendingTaskLine>  $lines
     */
    private function resolver(array $lines = []): PendingTaskEditableLineResolver
    {
        $linesById = [];

        foreach ($lines as $line) {
            $linesById[$line->id] = $line;
        }

        return new class($linesById) extends PendingTaskEditableLineResolver
        {
            /**
             * @param  array<int, PendingTaskLine>  $linesById
             */
            public function __construct(private readonly array $linesById) {}

            protected function findLine(?int $lineId): ?PendingTaskLine
            {
                if (! is_int($lineId)) {
                    return null;
                }

                return $this->linesById[$lineId] ?? null;
            }
        };
    }

    private function makeLine(int $id, int $pendingTaskId, PendingTaskLineStatus $status = PendingTaskLineStatus::Pending): PendingTaskLine
    {
        $line = new PendingTaskLine;
        $line->id = $id;
        $line->pending_task_id = $pendingTaskId;
        $line->line_status = $status;

        return $line;
    }
}
