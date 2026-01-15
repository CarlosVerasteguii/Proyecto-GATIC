<?php

declare(strict_types=1);

namespace App\Support\Assets;

use App\Exceptions\AssetTransitionException;
use App\Models\Asset;

/**
 * Central source of truth for Asset status transition rules.
 *
 * Provides:
 * - `can*` methods for UI enablement checks
 * - `assertCan*` methods for server-side validation (throws on failure)
 */
final class AssetStatusTransitions
{
    /**
     * Statuses from which an asset can be assigned to an employee.
     *
     * @var list<string>
     */
    private const ASSIGNABLE_FROM = [
        Asset::STATUS_AVAILABLE,
    ];

    /**
     * Statuses from which an asset can be loaned.
     *
     * @var list<string>
     */
    private const LOANABLE_FROM = [
        Asset::STATUS_AVAILABLE,
    ];

    /**
     * Statuses from which a loan can be returned.
     *
     * @var list<string>
     */
    private const RETURNABLE_FROM = [
        Asset::STATUS_LOANED,
    ];

    /**
     * Statuses from which an asset can be unassigned.
     *
     * @var list<string>
     */
    private const UNASSIGNABLE_FROM = [
        Asset::STATUS_ASSIGNED,
    ];

    /**
     * Statuses that block all operational actions.
     *
     * @var list<string>
     */
    private const BLOCKED_STATUSES = [
        Asset::STATUS_RETIRED,
    ];

    // -------------------------------------------------------------------------
    // can* methods (for UI enablement)
    // -------------------------------------------------------------------------

    public static function canAssign(string $currentStatus): bool
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            return false;
        }

        return in_array($currentStatus, self::ASSIGNABLE_FROM, true);
    }

    public static function canLoan(string $currentStatus): bool
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            return false;
        }

        return in_array($currentStatus, self::LOANABLE_FROM, true);
    }

    public static function canReturn(string $currentStatus): bool
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            return false;
        }

        return in_array($currentStatus, self::RETURNABLE_FROM, true);
    }

    public static function canUnassign(string $currentStatus): bool
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            return false;
        }

        return in_array($currentStatus, self::UNASSIGNABLE_FROM, true);
    }

    // -------------------------------------------------------------------------
    // assertCan* methods (for server-side validation - throw on failure)
    // -------------------------------------------------------------------------

    /**
     * @throws AssetTransitionException
     */
    public static function assertCanAssign(string $currentStatus): void
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            throw AssetTransitionException::blockedStatus($currentStatus, 'asignar');
        }

        if ($currentStatus === Asset::STATUS_LOANED) {
            throw AssetTransitionException::mustReturnFirst();
        }

        if ($currentStatus === Asset::STATUS_ASSIGNED) {
            throw AssetTransitionException::alreadyAssigned();
        }

        if ($currentStatus === Asset::STATUS_PENDING_RETIREMENT) {
            throw AssetTransitionException::pendingRetirement('asignar');
        }

        if (! in_array($currentStatus, self::ASSIGNABLE_FROM, true)) {
            throw AssetTransitionException::invalidTransition($currentStatus, 'asignar');
        }
    }

    /**
     * @throws AssetTransitionException
     */
    public static function assertCanLoan(string $currentStatus): void
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            throw AssetTransitionException::blockedStatus($currentStatus, 'prestar');
        }

        if ($currentStatus === Asset::STATUS_ASSIGNED) {
            throw AssetTransitionException::mustUnassignFirst();
        }

        if ($currentStatus === Asset::STATUS_LOANED) {
            throw AssetTransitionException::alreadyLoaned();
        }

        if ($currentStatus === Asset::STATUS_PENDING_RETIREMENT) {
            throw AssetTransitionException::pendingRetirement('prestar');
        }

        if (! in_array($currentStatus, self::LOANABLE_FROM, true)) {
            throw AssetTransitionException::invalidTransition($currentStatus, 'prestar');
        }
    }

    /**
     * @throws AssetTransitionException
     */
    public static function assertCanReturn(string $currentStatus): void
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            throw AssetTransitionException::blockedStatus($currentStatus, 'devolver');
        }

        if ($currentStatus !== Asset::STATUS_LOANED) {
            throw AssetTransitionException::notLoaned();
        }
    }

    /**
     * @throws AssetTransitionException
     */
    public static function assertCanUnassign(string $currentStatus): void
    {
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            throw AssetTransitionException::blockedStatus($currentStatus, 'desasignar');
        }

        if ($currentStatus !== Asset::STATUS_ASSIGNED) {
            throw AssetTransitionException::notAssigned();
        }
    }

    // -------------------------------------------------------------------------
    // Utility: get blocking reason for UI display
    // -------------------------------------------------------------------------

    /**
     * Get human-readable reason why an action is blocked.
     *
     * @param string $action One of: assign, loan, return, unassign
     * @return string|null Null if action is allowed
     */
    public static function getBlockingReason(string $currentStatus, string $action): ?string
    {
        if (! in_array($action, ['assign', 'loan', 'return', 'unassign'], true)) {
            return "Acción inválida: \"{$action}\".";
        }

        try {
            match ($action) {
                'assign' => self::assertCanAssign($currentStatus),
                'loan' => self::assertCanLoan($currentStatus),
                'return' => self::assertCanReturn($currentStatus),
                'unassign' => self::assertCanUnassign($currentStatus),
            };

            return null;
        } catch (AssetTransitionException $e) {
            return $e->getMessage();
        }
    }
}
