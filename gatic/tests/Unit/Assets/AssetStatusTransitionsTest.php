<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use App\Exceptions\AssetTransitionException;
use App\Models\Asset;
use App\Support\Assets\AssetStatusTransitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AssetStatusTransitions rules.
 *
 * Tests the transition matrix for serialized assets:
 * - AC1: Asignado no se presta (must unassign first)
 * - AC2: Prestado no se reasigna (must return first)
 * - Retirado blocks all operational actions
 * - Invalid UI actions are treated as blocked with a clear message
 */
class AssetStatusTransitionsTest extends TestCase
{
    // =========================================================================
    // AC1: Asignado no se presta
    // =========================================================================

    #[Test]
    public function assigned_asset_cannot_be_loaned(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canLoan(Asset::STATUS_ASSIGNED)
        );
    }

    #[Test]
    public function assigned_asset_loan_attempt_throws_with_actionable_message(): void
    {
        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Debe desasignarlo primero');

        AssetStatusTransitions::assertCanLoan(Asset::STATUS_ASSIGNED);
    }

    // =========================================================================
    // AC2: Prestado no se reasigna
    // =========================================================================

    #[Test]
    public function loaned_asset_cannot_be_assigned(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canAssign(Asset::STATUS_LOANED)
        );
    }

    #[Test]
    public function loaned_asset_assign_attempt_throws_with_actionable_message(): void
    {
        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Debe devolverlo primero');

        AssetStatusTransitions::assertCanAssign(Asset::STATUS_LOANED);
    }

    #[Test]
    public function already_assigned_asset_assign_attempt_throws_with_actionable_message(): void
    {
        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Debe desasignarlo primero');

        AssetStatusTransitions::assertCanAssign(Asset::STATUS_ASSIGNED);
    }

    // =========================================================================
    // Retirado blocks all operational actions
    // =========================================================================

    #[Test]
    #[DataProvider('operationalActionsProvider')]
    public function retired_asset_blocks_all_operational_actions(string $canMethod, string $assertMethod): void
    {
        // can* method returns false
        $this->assertFalse(
            AssetStatusTransitions::$canMethod(Asset::STATUS_RETIRED)
        );

        // assert* method throws with clear message
        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Retirado');

        AssetStatusTransitions::$assertMethod(Asset::STATUS_RETIRED);
    }

    /**
     * @return array<string, array{canMethod: string, assertMethod: string}>
     */
    public static function operationalActionsProvider(): array
    {
        return [
            'assign' => ['canMethod' => 'canAssign', 'assertMethod' => 'assertCanAssign'],
            'loan' => ['canMethod' => 'canLoan', 'assertMethod' => 'assertCanLoan'],
            'return' => ['canMethod' => 'canReturn', 'assertMethod' => 'assertCanReturn'],
            'unassign' => ['canMethod' => 'canUnassign', 'assertMethod' => 'assertCanUnassign'],
        ];
    }

    // =========================================================================
    // Happy paths: Disponible allows assign and loan
    // =========================================================================

    #[Test]
    public function available_asset_can_be_assigned(): void
    {
        $this->assertTrue(
            AssetStatusTransitions::canAssign(Asset::STATUS_AVAILABLE)
        );

        // Should not throw
        AssetStatusTransitions::assertCanAssign(Asset::STATUS_AVAILABLE);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function available_asset_can_be_loaned(): void
    {
        $this->assertTrue(
            AssetStatusTransitions::canLoan(Asset::STATUS_AVAILABLE)
        );

        // Should not throw
        AssetStatusTransitions::assertCanLoan(Asset::STATUS_AVAILABLE);
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // Happy paths: Return and Unassign
    // =========================================================================

    #[Test]
    public function loaned_asset_can_be_returned(): void
    {
        $this->assertTrue(
            AssetStatusTransitions::canReturn(Asset::STATUS_LOANED)
        );

        // Should not throw
        AssetStatusTransitions::assertCanReturn(Asset::STATUS_LOANED);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assigned_asset_can_be_unassigned(): void
    {
        $this->assertTrue(
            AssetStatusTransitions::canUnassign(Asset::STATUS_ASSIGNED)
        );

        // Should not throw
        AssetStatusTransitions::assertCanUnassign(Asset::STATUS_ASSIGNED);
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // Edge cases: Invalid return/unassign attempts
    // =========================================================================

    #[Test]
    public function available_asset_cannot_be_returned(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canReturn(Asset::STATUS_AVAILABLE)
        );

        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Solo se pueden devolver activos');

        AssetStatusTransitions::assertCanReturn(Asset::STATUS_AVAILABLE);
    }

    #[Test]
    public function available_asset_cannot_be_unassigned(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canUnassign(Asset::STATUS_AVAILABLE)
        );

        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Solo se pueden desasignar activos');

        AssetStatusTransitions::assertCanUnassign(Asset::STATUS_AVAILABLE);
    }

    // =========================================================================
    // Pendiente de Retiro blocks operational actions
    // =========================================================================

    #[Test]
    public function pending_retirement_blocks_assign(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canAssign(Asset::STATUS_PENDING_RETIREMENT)
        );

        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('pendiente de retiro');

        AssetStatusTransitions::assertCanAssign(Asset::STATUS_PENDING_RETIREMENT);
    }

    #[Test]
    public function pending_retirement_blocks_loan(): void
    {
        $this->assertFalse(
            AssetStatusTransitions::canLoan(Asset::STATUS_PENDING_RETIREMENT)
        );

        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('pendiente de retiro');

        AssetStatusTransitions::assertCanLoan(Asset::STATUS_PENDING_RETIREMENT);
    }

    #[Test]
    public function already_loaned_asset_loan_attempt_throws_with_actionable_message(): void
    {
        $this->expectException(AssetTransitionException::class);
        $this->expectExceptionMessage('Debe devolverlo primero');

        AssetStatusTransitions::assertCanLoan(Asset::STATUS_LOANED);
    }

    // =========================================================================
    // getBlockingReason utility
    // =========================================================================

    #[Test]
    public function get_blocking_reason_returns_null_when_allowed(): void
    {
        $reason = AssetStatusTransitions::getBlockingReason(
            Asset::STATUS_AVAILABLE,
            'assign'
        );

        $this->assertNull($reason);
    }

    #[Test]
    public function get_blocking_reason_returns_message_when_blocked(): void
    {
        $reason = AssetStatusTransitions::getBlockingReason(
            Asset::STATUS_ASSIGNED,
            'loan'
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('desasignarlo primero', $reason);
    }

    #[Test]
    public function get_blocking_reason_returns_message_for_unknown_action(): void
    {
        $reason = AssetStatusTransitions::getBlockingReason(
            Asset::STATUS_AVAILABLE,
            'ship'
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Acción inválida', $reason);
    }
}
