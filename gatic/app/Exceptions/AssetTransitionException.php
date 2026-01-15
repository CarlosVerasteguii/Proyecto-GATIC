<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;

/**
 * Exception thrown when an asset status transition is not allowed.
 *
 * Contains user-facing messages in Spanish with actionable guidance.
 */
class AssetTransitionException extends Exception
{
    public function toValidationException(string $field = 'asset'): ValidationException
    {
        return ValidationException::withMessages([
            $field => $this->getMessage(),
        ]);
    }

    public static function mustUnassignFirst(): self
    {
        return new self('El activo está asignado. Debe desasignarlo primero antes de prestarlo.');
    }

    public static function mustReturnFirst(): self
    {
        return new self('El activo está prestado. Debe devolverlo primero antes de asignarlo.');
    }

    public static function alreadyAssigned(): self
    {
        return new self('El activo ya está asignado. Debe desasignarlo primero para reasignarlo.');
    }

    public static function alreadyLoaned(): self
    {
        return new self('El activo ya está prestado. Debe devolverlo primero.');
    }

    public static function notLoaned(): self
    {
        return new self('El activo no está prestado. Solo se pueden devolver activos en estado Prestado.');
    }

    public static function notAssigned(): self
    {
        return new self('El activo no está asignado. Solo se pueden desasignar activos en estado Asignado.');
    }

    public static function blockedStatus(string $status, string $action): self
    {
        return new self("No se puede {$action} un activo en estado \"{$status}\".");
    }

    public static function pendingRetirement(string $action): self
    {
        return new self("El activo está pendiente de retiro. Resuelva el retiro antes de {$action}.");
    }

    public static function invalidTransition(string $currentStatus, string $action): self
    {
        return new self("Transición inválida: no se puede {$action} un activo en estado \"{$currentStatus}\".");
    }
}
