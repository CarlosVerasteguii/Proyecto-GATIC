<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Support\PendingTasks\SerializedBulkPasteAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerializedBulkPasteAnalyzerTest extends TestCase
{
    #[Test]
    public function it_parses_serials_preserving_line_numbers_and_ignoring_blank_lines(): void
    {
        $analysis = (new SerializedBulkPasteAnalyzer)->analyze(" ABC123 \n\nDEF456\r\n", 200);

        $this->assertSame(['ABC123', 'DEF456'], $analysis->serials);
        $this->assertSame(2, $analysis->count);
        $this->assertSame(2, $analysis->okCount);
        $this->assertSame(0, $analysis->duplicateCount);
        $this->assertSame(0, $analysis->invalidCount);
        $this->assertNull($analysis->limitError);
        $this->assertSame(1, $analysis->preview[0]['line']);
        $this->assertSame(3, $analysis->preview[1]['line']);
    }

    #[Test]
    public function it_marks_invalid_serials_without_treating_them_as_duplicates(): void
    {
        $analysis = (new SerializedBulkPasteAnalyzer)->analyze("OK123\nINV@LID\nAA", 200);

        $this->assertSame(3, $analysis->count);
        $this->assertSame(1, $analysis->okCount);
        $this->assertSame(0, $analysis->duplicateCount);
        $this->assertSame(2, $analysis->invalidCount);
        $this->assertSame('ok', $analysis->preview[0]['status']);
        $this->assertSame('invalid', $analysis->preview[1]['status']);
        $this->assertSame(
            'El serial debe contener solo caracteres alfanuméricos, guiones y guiones bajos.',
            $analysis->preview[1]['message']
        );
        $this->assertSame('invalid', $analysis->preview[2]['status']);
        $this->assertSame('El serial debe tener al menos 3 caracteres.', $analysis->preview[2]['message']);
    }

    #[Test]
    public function it_marks_duplicates_from_paste_and_existing_task_lines(): void
    {
        $analysis = (new SerializedBulkPasteAnalyzer)->analyze(
            "DUP001\nDUP001\nEXIST001",
            200,
            ['EXIST001']
        );

        $this->assertSame(3, $analysis->count);
        $this->assertSame(0, $analysis->okCount);
        $this->assertSame(3, $analysis->duplicateCount);
        $this->assertSame(0, $analysis->invalidCount);
        $this->assertSame('duplicate', $analysis->preview[0]['status']);
        $this->assertSame('Repetida en el pegado', $analysis->preview[0]['message']);
        $this->assertSame('duplicate', $analysis->preview[2]['status']);
        $this->assertSame('Ya existe en la tarea', $analysis->preview[2]['message']);
    }

    #[Test]
    public function it_marks_rows_beyond_the_configured_limit_as_invalid(): void
    {
        $analysis = (new SerializedBulkPasteAnalyzer)->analyze("ABC123\nABC124\nABC125", 2);

        $this->assertSame(3, $analysis->count);
        $this->assertSame(2, $analysis->okCount);
        $this->assertSame(0, $analysis->duplicateCount);
        $this->assertSame(1, $analysis->invalidCount);
        $this->assertSame(
            'Límite máximo: 2 líneas. Reduce el pegado para poder guardar.',
            $analysis->limitError
        );
        $this->assertSame('invalid', $analysis->preview[2]['status']);
        $this->assertSame('Excede el límite de 2 líneas.', $analysis->preview[2]['message']);
    }
}
