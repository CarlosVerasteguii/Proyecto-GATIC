<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Actions\PendingTasks\Concerns\ValidatesTaskLines;
use Illuminate\Validation\ValidationException;

/**
 * Builds the serialized bulk-paste preview contract used by PendingTaskShow.
 */
final class SerializedBulkPasteAnalyzer
{
    use ValidatesTaskLines;

    /**
     * @param  iterable<string>  $existingSerials
     */
    public function analyze(string $input, int $maxLines, iterable $existingSerials = []): SerializedBulkPasteAnalysis
    {
        $parsed = $this->parse($input);
        $serials = array_map(
            static fn (array $item): string => $item['value'],
            $parsed,
        );

        if ($parsed === []) {
            return new SerializedBulkPasteAnalysis(
                preview: [],
                serials: [],
                count: 0,
                okCount: 0,
                duplicateCount: 0,
                invalidCount: 0,
                limitError: null,
            );
        }

        $max = max(1, $maxLines);
        $counts = array_count_values($serials);
        $existingSerialsSet = $this->existingSerialsSet($existingSerials);

        $preview = [];
        $okCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;
        $limitError = count($parsed) > $max
            ? "Límite máximo: {$max} líneas. Reduce el pegado para poder guardar."
            : null;

        foreach ($parsed as $index => $item) {
            $status = 'ok';
            $statusLabel = 'OK';
            $message = null;
            $value = $item['value'];

            if ($index >= $max) {
                $status = 'invalid';
                $statusLabel = 'Inválida';
                $message = "Excede el límite de {$max} líneas.";
            } else {
                $validationError = $this->serializedValidationError($value);

                if ($validationError !== null) {
                    $status = 'invalid';
                    $statusLabel = 'Inválida';
                    $message = $validationError;
                } else {
                    $duplicateReasons = [];

                    if (($counts[$value] ?? 0) > 1) {
                        $duplicateReasons[] = 'Repetida en el pegado';
                    }

                    if (isset($existingSerialsSet[$value])) {
                        $duplicateReasons[] = 'Ya existe en la tarea';
                    }

                    if ($duplicateReasons !== []) {
                        $status = 'duplicate';
                        $statusLabel = 'Duplicada';
                        $message = implode(' · ', $duplicateReasons);
                    }
                }
            }

            $preview[] = [
                'line' => $item['line'],
                'value' => $value,
                'status' => $status,
                'status_label' => $statusLabel,
                'message' => $message,
            ];

            if ($status === 'ok') {
                $okCount++;
            } elseif ($status === 'duplicate') {
                $duplicateCount++;
            } else {
                $invalidCount++;
            }
        }

        return new SerializedBulkPasteAnalysis(
            preview: $preview,
            serials: $serials,
            count: count($parsed),
            okCount: $okCount,
            duplicateCount: $duplicateCount,
            invalidCount: $invalidCount,
            limitError: $limitError,
        );
    }

    /**
     * @return list<array{line: int, value: string}>
     */
    private function parse(string $input): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $input) ?: [];
        $parsed = [];

        foreach ($lines as $index => $line) {
            $value = trim($line);
            if ($value === '') {
                continue;
            }

            $parsed[] = [
                'line' => $index + 1,
                'value' => $value,
            ];
        }

        return $parsed;
    }

    /**
     * @param  iterable<string>  $existingSerials
     * @return array<string, true>
     */
    private function existingSerialsSet(iterable $existingSerials): array
    {
        $set = [];

        foreach ($existingSerials as $serial) {
            $value = trim($serial);

            if ($value === '') {
                continue;
            }

            $set[$value] = true;
        }

        return $set;
    }

    private function serializedValidationError(string $value): ?string
    {
        try {
            $this->validateSerializedLine([
                'serial' => $value,
                'asset_tag' => null,
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            return $errors['serial'][0] ?? $errors['asset_tag'][0] ?? $e->getMessage();
        }

        return null;
    }
}
