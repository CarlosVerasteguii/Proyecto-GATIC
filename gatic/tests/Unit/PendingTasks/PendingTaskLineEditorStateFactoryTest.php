<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Enums\PendingTaskLineType;
use App\Models\PendingTaskLine;
use App\Support\PendingTasks\PendingTaskLineEditorStateFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskLineEditorStateFactoryTest extends TestCase
{
    #[Test]
    public function it_returns_the_default_draft_editor_state(): void
    {
        $state = (new PendingTaskLineEditorStateFactory)->resetDraftEditor();

        $this->assertSame([
            'lineType' => '',
            'productId' => null,
            'serial' => '',
            'assetTag' => '',
            'quantity' => null,
            'serializedBulkInput' => '',
            'serializedBulkPreview' => [],
            'serializedBulkCount' => 0,
            'serializedBulkOkCount' => 0,
            'serializedBulkDuplicateCount' => 0,
            'serializedBulkInvalidCount' => 0,
            'serializedBulkLimitError' => null,
            'employeeId' => null,
            'note' => '',
            'editingLineId' => null,
        ], $state);
    }

    #[Test]
    public function it_hydrates_the_draft_editor_from_a_line(): void
    {
        $line = new PendingTaskLine;
        $line->id = 55;
        $line->line_type = PendingTaskLineType::Serialized;
        $line->product_id = 14;
        $line->serial = 'SER-001';
        $line->asset_tag = 'TAG-001';
        $line->quantity = null;
        $line->employee_id = 22;
        $line->note = 'Nota de edicion';

        $state = (new PendingTaskLineEditorStateFactory)->hydrateDraftEditor($line);

        $this->assertSame([
            'editingLineId' => 55,
            'lineType' => PendingTaskLineType::Serialized->value,
            'productId' => 14,
            'serial' => 'SER-001',
            'assetTag' => 'TAG-001',
            'quantity' => null,
            'employeeId' => 22,
            'note' => 'Nota de edicion',
        ], $state);
    }

    #[Test]
    public function it_returns_the_default_process_editor_state(): void
    {
        $state = (new PendingTaskLineEditorStateFactory)->resetProcessEditor();

        $this->assertSame([
            'editingProcessLineId' => null,
            'processLineSerial' => '',
            'processLineAssetTag' => '',
            'processLineQuantity' => null,
            'processLineEmployeeId' => null,
            'processLineNote' => '',
        ], $state);
    }

    #[Test]
    public function it_hydrates_the_process_editor_from_a_line(): void
    {
        $line = new PendingTaskLine;
        $line->id = 77;
        $line->line_type = PendingTaskLineType::Quantity;
        $line->product_id = 19;
        $line->serial = null;
        $line->asset_tag = null;
        $line->quantity = 6;
        $line->employee_id = 31;
        $line->note = 'Nota de proceso';

        $state = (new PendingTaskLineEditorStateFactory)->hydrateProcessEditor($line);

        $this->assertSame([
            'editingProcessLineId' => 77,
            'processLineSerial' => '',
            'processLineAssetTag' => '',
            'processLineQuantity' => '6',
            'processLineEmployeeId' => 31,
            'processLineNote' => 'Nota de proceso',
        ], $state);
    }
}
