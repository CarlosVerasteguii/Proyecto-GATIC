<?php

namespace App\Actions\PendingTasks\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Shared validation logic for pending task lines.
 *
 * Used by AddLineToTask and UpdateTaskLine actions to avoid code duplication.
 */
trait ValidatesTaskLines
{
    /**
     * Validate a serialized line (requires serial and/or asset_tag).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validateSerializedLine(array $data): void
    {
        $serial = trim($data['serial'] ?? '');
        $assetTag = trim($data['asset_tag'] ?? '');

        if ($serial === '' && $assetTag === '') {
            throw ValidationException::withMessages([
                'serial' => ['Debe proporcionar al menos un serial o asset tag.'],
            ]);
        }

        if ($serial !== '' && strlen($serial) < 3) {
            throw ValidationException::withMessages([
                'serial' => ['El serial debe tener al menos 3 caracteres.'],
            ]);
        }

        if ($assetTag !== '' && strlen($assetTag) < 3) {
            throw ValidationException::withMessages([
                'asset_tag' => ['El asset tag debe tener al menos 3 caracteres.'],
            ]);
        }

        // Validate alphanumeric format (allowing hyphens and underscores)
        if ($serial !== '' && ! preg_match('/^[a-zA-Z0-9\-_]+$/', $serial)) {
            throw ValidationException::withMessages([
                'serial' => ['El serial debe contener solo caracteres alfanuméricos, guiones y guiones bajos.'],
            ]);
        }

        if ($assetTag !== '' && ! preg_match('/^[a-zA-Z0-9\-_]+$/', $assetTag)) {
            throw ValidationException::withMessages([
                'asset_tag' => ['El asset tag debe contener solo caracteres alfanuméricos, guiones y guiones bajos.'],
            ]);
        }
    }

    /**
     * Validate a quantity line (requires quantity > 0).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validateQuantityLine(array $data): void
    {
        $quantity = $data['quantity'] ?? null;

        if ($quantity === null || ! is_int($quantity) || $quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad debe ser un número entero mayor a 0.'],
            ]);
        }
    }
}
