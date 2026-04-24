<?php

declare(strict_types=1);

namespace Tests\Unit\PendingTasks;

use App\Support\PendingTasks\PendingTaskLineValidationErrorMapper;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingTaskLineValidationErrorMapperTest extends TestCase
{
    #[Test]
    public function it_maps_bulk_serial_errors_to_the_draft_editor_field(): void
    {
        $mappedErrors = (new PendingTaskLineValidationErrorMapper)->forDraftEditor(
            ValidationException::withMessages([
                'serials' => ['Serie inválida.'],
                'quantity' => ['La cantidad debe ser un entero.'],
            ]),
        );

        $this->assertSame([
            'serializedBulkInput' => 'Serie inválida.',
            'quantity' => 'La cantidad debe ser un entero.',
        ], $mappedErrors);
    }

    #[Test]
    public function it_maps_process_editor_fields_to_their_livewire_names(): void
    {
        $mappedErrors = (new PendingTaskLineValidationErrorMapper)->forProcessEditor(
            ValidationException::withMessages([
                'serial' => ['Debe proporcionar al menos un serial o asset tag.'],
                'asset_tag' => ['El asset tag es inválido.'],
                'quantity' => ['La cantidad debe ser mayor a 0.'],
                'employee_id' => ['El empleado es obligatorio.'],
                'note' => ['La nota es obligatoria.'],
            ]),
        );

        $this->assertSame([
            'processLineSerial' => 'Debe proporcionar al menos un serial o asset tag.',
            'processLineAssetTag' => 'El asset tag es inválido.',
            'processLineQuantity' => 'La cantidad debe ser mayor a 0.',
            'processLineEmployeeId' => 'El empleado es obligatorio.',
            'processLineNote' => 'La nota es obligatoria.',
        ], $mappedErrors);
    }

    #[Test]
    public function it_keeps_the_first_message_per_field_and_preserves_unknown_fields(): void
    {
        $mappedErrors = (new PendingTaskLineValidationErrorMapper)->forProcessEditor(
            ValidationException::withMessages([
                'quantity' => ['Primer mensaje', 'Segundo mensaje'],
                'pending_task_id' => ['La tarea no está disponible.'],
            ]),
        );

        $this->assertSame([
            'processLineQuantity' => 'Primer mensaje',
            'pending_task_id' => 'La tarea no está disponible.',
        ], $mappedErrors);
    }
}
