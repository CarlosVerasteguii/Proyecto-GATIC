<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use Illuminate\Validation\ValidationException;

final class PendingTaskLineValidationErrorMapper
{
    /**
     * @return array<string, string>
     */
    public function forDraftEditor(ValidationException $exception): array
    {
        return $this->map($exception, [
            'serials' => 'serializedBulkInput',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function forProcessEditor(ValidationException $exception): array
    {
        return $this->map($exception, [
            'serial' => 'processLineSerial',
            'asset_tag' => 'processLineAssetTag',
            'quantity' => 'processLineQuantity',
            'employee_id' => 'processLineEmployeeId',
            'note' => 'processLineNote',
        ]);
    }

    /**
     * @param  array<string, string>  $fieldMap
     * @return array<string, string>
     */
    private function map(ValidationException $exception, array $fieldMap): array
    {
        $mappedErrors = [];

        foreach ($exception->errors() as $field => $messages) {
            $mappedErrors[$fieldMap[$field] ?? $field] = $messages[0] ?? $exception->getMessage();
        }

        return $mappedErrors;
    }
}
