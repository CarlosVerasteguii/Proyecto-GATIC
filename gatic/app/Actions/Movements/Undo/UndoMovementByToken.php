<?php

declare(strict_types=1);

namespace App\Actions\Movements\Undo;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AuditLog;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\UndoToken;
use App\Models\User;
use App\Support\Assets\AssetStatusTransitions;
use App\Support\Audit\AuditRecorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UndoMovementByToken
{
    /**
     * @param  array{token_id: string, actor_user_id: int}  $data
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        Validator::make($data, [
            'token_id' => ['required', 'string', 'uuid'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ], [
            'token_id.required' => 'El token es obligatorio.',
            'token_id.uuid' => 'El token no es válido.',
            'actor_user_id.required' => 'El usuario es obligatorio.',
            'actor_user_id.exists' => 'El usuario no existe.',
        ])->validate();

        /** @var User $actor */
        $actor = User::query()->findOrFail($data['actor_user_id']);

        return DB::transaction(function () use ($data, $actor): array {
            /** @var UndoToken|null $token */
            $token = UndoToken::query()
                ->lockForUpdate()
                ->find($data['token_id']);

            if (! $token) {
                throw ValidationException::withMessages([
                    'token' => 'El token de deshacer no es válido.',
                ]);
            }

            if ($token->actor_user_id !== $actor->id && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'token' => 'Solo el usuario que realizó el movimiento puede deshacerlo.',
                ]);
            }

            if ($token->used_at !== null) {
                return [
                    'type' => 'info',
                    'title' => 'Acción ya aplicada',
                    'message' => 'Este movimiento ya fue deshecho.',
                    'context' => [],
                ];
            }

            if ($token->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'token' => 'La ventana para deshacer ya expiró.',
                ]);
            }

            $movementId = $token->movement_id;
            $batchUuid = $token->batch_uuid;

            if (($movementId === null && $batchUuid === null) || ($movementId !== null && $batchUuid !== null)) {
                throw ValidationException::withMessages([
                    'token' => 'El token de deshacer es inválido (referencia ambigua).',
                ]);
            }

            $result = $batchUuid !== null
                ? $this->undoAssetBatch(batchUuid: $batchUuid, actor: $actor)
                : $this->undoSingleMovement(
                    kind: $token->movement_kind,
                    movementId: $movementId,
                    actor: $actor,
                );

            $token->used_at = now();
            $token->used_by_user_id = $actor->id;
            $token->save();

            return $result;
        });
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoSingleMovement(string $kind, int $movementId, User $actor): array
    {
        return match ($kind) {
            UndoToken::KIND_ASSET_MOVEMENT => $this->undoSingleAssetMovement(movementId: $movementId, actor: $actor),
            UndoToken::KIND_PRODUCT_QTY_MOVEMENT => $this->undoSingleProductQtyMovement(movementId: $movementId, actor: $actor),
            default => throw ValidationException::withMessages([
                'token' => 'El token de deshacer es inválido (tipo desconocido).',
            ]),
        };
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoSingleAssetMovement(int $movementId, User $actor): array
    {
        /** @var AssetMovement|null $movement */
        $movement = AssetMovement::query()->find($movementId);

        if (! $movement) {
            throw ValidationException::withMessages([
                'token' => 'No se encontró el movimiento a deshacer.',
            ]);
        }

        /** @var Asset $asset */
        $asset = Asset::query()
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->findOrFail($movement->asset_id);

        $lastMovementId = AssetMovement::query()
            ->where('asset_id', $asset->id)
            ->max('id');

        if ($lastMovementId !== $movement->id) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque existen movimientos posteriores para este activo.',
            ]);
        }

        $hasLaterAdjustment = InventoryAdjustmentEntry::query()
            ->where('subject_type', Asset::class)
            ->where('subject_id', $asset->id)
            ->where('created_at', '>', $movement->created_at)
            ->exists();

        if ($hasLaterAdjustment) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque existe un ajuste de inventario posterior para este activo.',
            ]);
        }

        return match ($movement->type) {
            AssetMovement::TYPE_ASSIGN => $this->undoAssetAssign($asset, $movement, $actor),
            AssetMovement::TYPE_UNASSIGN => $this->undoAssetUnassign($asset, $movement, $actor),
            AssetMovement::TYPE_LOAN => $this->undoAssetLoan($asset, $movement, $actor),
            AssetMovement::TYPE_RETURN => $this->undoAssetReturn($asset, $movement, $actor),
            default => throw ValidationException::withMessages([
                'token' => 'No se puede deshacer este tipo de movimiento.',
            ]),
        };
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoSingleProductQtyMovement(int $movementId, User $actor): array
    {
        /** @var ProductQuantityMovement|null $movement */
        $movement = ProductQuantityMovement::query()->find($movementId);

        if (! $movement) {
            throw ValidationException::withMessages([
                'token' => 'No se encontró el movimiento a deshacer.',
            ]);
        }

        /** @var Product $product */
        $product = Product::query()
            ->with('category')
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->findOrFail($movement->product_id);

        if ($product->category?->is_serialized) {
            throw ValidationException::withMessages([
                'token' => 'Este producto es serializado. Esta operación solo aplica a productos no serializados.',
            ]);
        }

        if ($product->qty_total === null) {
            throw ValidationException::withMessages([
                'token' => 'El stock actual de este producto no está inicializado.',
            ]);
        }

        $lastMovementId = ProductQuantityMovement::query()
            ->where('product_id', $product->id)
            ->max('id');

        if ($lastMovementId !== $movement->id) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque existen movimientos posteriores para este producto.',
            ]);
        }

        $hasLaterAdjustment = InventoryAdjustmentEntry::query()
            ->where('subject_type', Product::class)
            ->where('subject_id', $product->id)
            ->where('created_at', '>', $movement->created_at)
            ->exists();

        if ($hasLaterAdjustment) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque existe un ajuste de inventario posterior para este producto.',
            ]);
        }

        $expectedQtyTotal = (int) $movement->qty_after;

        if ((int) $product->qty_total !== $expectedQtyTotal) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque el stock actual ya cambió.',
            ]);
        }

        $undoDirection = $movement->direction === ProductQuantityMovement::DIRECTION_OUT
            ? ProductQuantityMovement::DIRECTION_IN
            : ProductQuantityMovement::DIRECTION_OUT;

        $qtyBefore = (int) $product->qty_total;
        $qtyAfter = (int) $movement->qty_before;

        $product->qty_total = $qtyAfter;
        $product->save();

        $undoMovement = ProductQuantityMovement::create([
            'product_id' => $product->id,
            'employee_id' => $movement->employee_id,
            'actor_user_id' => $actor->id,
            'direction' => $undoDirection,
            'qty' => (int) $movement->qty,
            'qty_before' => $qtyBefore,
            'qty_after' => $qtyAfter,
            'note' => $this->buildUndoNote(
                label: 'movimiento',
                originalType: $movement->direction,
                originalId: $movement->id,
                originalNote: $movement->note,
            ),
        ]);

        AuditRecorder::record(
            action: AuditLog::ACTION_PRODUCT_QTY_REGISTER,
            subjectType: ProductQuantityMovement::class,
            subjectId: $undoMovement->id,
            actorUserId: $actor->id,
            context: [
                'product_id' => $product->id,
                'employee_id' => $movement->employee_id,
                'reason' => 'undo',
                'movement_id' => $movement->id,
                'summary' => "direction={$movement->direction} -> {$undoDirection}; qty={$movement->qty}; qty_total: {$qtyBefore} -> {$qtyAfter}",
            ]
        );

        return [
            'type' => 'success',
            'title' => 'Movimiento deshecho',
            'message' => 'Se deshizo el movimiento de cantidad correctamente.',
            'context' => [
                'product_id' => $product->id,
            ],
        ];
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoAssetBatch(string $batchUuid, User $actor): array
    {
        /** @var Collection<int, AssetMovement> $movements */
        $movements = AssetMovement::query()
            ->where('batch_uuid', $batchUuid)
            ->orderBy('asset_id')
            ->get();

        if ($movements->isEmpty()) {
            throw ValidationException::withMessages([
                'token' => 'No se encontró el batch a deshacer.',
            ]);
        }

        $hasUnsupported = $movements->contains(static fn (AssetMovement $m): bool => $m->type !== AssetMovement::TYPE_ASSIGN);
        if ($hasUnsupported) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer este batch (tipo de movimientos no soportado).',
            ]);
        }

        /** @var list<int> $assetIds */
        $assetIds = $movements
            ->pluck('asset_id')
            ->unique()
            ->values()
            ->all();

        if (count($assetIds) !== $movements->count()) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer este batch (activos duplicados).',
            ]);
        }

        $assets = Asset::query()
            ->whereNull('deleted_at')
            ->whereIn('id', $assetIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($assets->count() !== count($assetIds)) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer este batch porque uno o más activos no existen.',
            ]);
        }

        foreach ($movements as $movement) {
            /** @var Asset $asset */
            $asset = $assets->get($movement->asset_id);

            $lastMovementId = AssetMovement::query()
                ->where('asset_id', $asset->id)
                ->max('id');

            if ($lastMovementId !== $movement->id) {
                throw ValidationException::withMessages([
                    'token' => 'No se puede deshacer porque uno o más activos tienen movimientos posteriores.',
                ]);
            }

            $hasLaterAdjustment = InventoryAdjustmentEntry::query()
                ->where('subject_type', Asset::class)
                ->where('subject_id', $asset->id)
                ->where('created_at', '>', $movement->created_at)
                ->exists();

            if ($hasLaterAdjustment) {
                throw ValidationException::withMessages([
                    'token' => 'No se puede deshacer porque existe un ajuste de inventario posterior en uno o más activos.',
                ]);
            }

            if ($asset->status !== Asset::STATUS_ASSIGNED || $asset->current_employee_id !== $movement->employee_id) {
                throw ValidationException::withMessages([
                    'token' => 'No se puede deshacer porque el estado actual de uno o más activos ya cambió.',
                ]);
            }
        }

        foreach ($movements as $movement) {
            /** @var Asset $asset */
            $asset = $assets->get($movement->asset_id);

            AssetStatusTransitions::assertCanUnassign($asset->status);

            $asset->status = Asset::STATUS_AVAILABLE;
            $asset->current_employee_id = null;
            $asset->loan_due_date = null;
            $asset->save();

            $undoMovement = AssetMovement::create([
                'asset_id' => $asset->id,
                'employee_id' => $movement->employee_id,
                'actor_user_id' => $actor->id,
                'batch_uuid' => $batchUuid,
                'type' => AssetMovement::TYPE_UNASSIGN,
                'loan_due_date' => null,
                'note' => $this->buildUndoNote(
                    label: 'batch',
                    originalType: $movement->type,
                    originalId: $movement->id,
                    originalNote: $movement->note,
                    batchUuid: $batchUuid,
                ),
            ]);

            AuditRecorder::record(
                action: AuditLog::ACTION_ASSET_UNASSIGN,
                subjectType: AssetMovement::class,
                subjectId: $undoMovement->id,
                actorUserId: $actor->id,
                context: [
                    'asset_id' => $asset->id,
                    'employee_id' => $movement->employee_id,
                    'reason' => 'undo_batch',
                    'movement_id' => $movement->id,
                ]
            );
        }

        $count = $movements->count();

        return [
            'type' => 'success',
            'title' => 'Asignación masiva deshecha',
            'message' => "Se deshizo la asignación de {$count} activos.",
            'context' => [
                'batch_uuid' => $batchUuid,
                'asset_ids' => $assetIds,
            ],
        ];
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoAssetAssign(Asset $asset, AssetMovement $movement, User $actor): array
    {
        if ($asset->status !== Asset::STATUS_ASSIGNED || $asset->current_employee_id !== $movement->employee_id) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque el activo ya no está asignado al mismo empleado.',
            ]);
        }

        AssetStatusTransitions::assertCanUnassign($asset->status);

        $asset->status = Asset::STATUS_AVAILABLE;
        $asset->current_employee_id = null;
        $asset->loan_due_date = null;
        $asset->save();

        $undoMovement = AssetMovement::create([
            'asset_id' => $asset->id,
            'employee_id' => $movement->employee_id,
            'actor_user_id' => $actor->id,
            'batch_uuid' => $movement->batch_uuid,
            'type' => AssetMovement::TYPE_UNASSIGN,
            'loan_due_date' => null,
            'note' => $this->buildUndoNote(
                label: 'movimiento',
                originalType: $movement->type,
                originalId: $movement->id,
                originalNote: $movement->note,
                batchUuid: $movement->batch_uuid,
            ),
        ]);

        AuditRecorder::record(
            action: AuditLog::ACTION_ASSET_UNASSIGN,
            subjectType: AssetMovement::class,
            subjectId: $undoMovement->id,
            actorUserId: $actor->id,
            context: [
                'asset_id' => $asset->id,
                'employee_id' => $movement->employee_id,
                'reason' => 'undo',
                'movement_id' => $movement->id,
            ]
        );

        return [
            'type' => 'success',
            'title' => 'Movimiento deshecho',
            'message' => 'Se deshizo la asignación del activo correctamente.',
            'context' => [
                'asset_id' => $asset->id,
                'product_id' => $asset->product_id,
            ],
        ];
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoAssetUnassign(Asset $asset, AssetMovement $movement, User $actor): array
    {
        if ($asset->status !== Asset::STATUS_AVAILABLE || $asset->current_employee_id !== null) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque el activo ya no está disponible.',
            ]);
        }

        AssetStatusTransitions::assertCanAssign($asset->status);

        $asset->status = Asset::STATUS_ASSIGNED;
        $asset->current_employee_id = $movement->employee_id;
        $asset->loan_due_date = null;
        $asset->save();

        $undoMovement = AssetMovement::create([
            'asset_id' => $asset->id,
            'employee_id' => $movement->employee_id,
            'actor_user_id' => $actor->id,
            'batch_uuid' => $movement->batch_uuid,
            'type' => AssetMovement::TYPE_ASSIGN,
            'loan_due_date' => null,
            'note' => $this->buildUndoNote(
                label: 'movimiento',
                originalType: $movement->type,
                originalId: $movement->id,
                originalNote: $movement->note,
                batchUuid: $movement->batch_uuid,
            ),
        ]);

        AuditRecorder::record(
            action: AuditLog::ACTION_ASSET_ASSIGN,
            subjectType: AssetMovement::class,
            subjectId: $undoMovement->id,
            actorUserId: $actor->id,
            context: [
                'asset_id' => $asset->id,
                'employee_id' => $movement->employee_id,
                'reason' => 'undo',
                'movement_id' => $movement->id,
            ]
        );

        return [
            'type' => 'success',
            'title' => 'Movimiento deshecho',
            'message' => 'Se deshizo la desasignación del activo correctamente.',
            'context' => [
                'asset_id' => $asset->id,
                'product_id' => $asset->product_id,
            ],
        ];
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoAssetLoan(Asset $asset, AssetMovement $movement, User $actor): array
    {
        if ($asset->status !== Asset::STATUS_LOANED || $asset->current_employee_id !== $movement->employee_id) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque el activo ya no está prestado al mismo empleado.',
            ]);
        }

        $assetDue = $asset->loan_due_date?->toDateString();
        $movementDue = $movement->loan_due_date?->toDateString();
        if ($assetDue !== $movementDue) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque la fecha de vencimiento del préstamo ya cambió.',
            ]);
        }

        AssetStatusTransitions::assertCanReturn($asset->status);

        $previousLoanDueDate = $asset->loan_due_date;

        $asset->status = Asset::STATUS_AVAILABLE;
        $asset->current_employee_id = null;
        $asset->loan_due_date = null;
        $asset->save();

        $undoMovement = AssetMovement::create([
            'asset_id' => $asset->id,
            'employee_id' => $movement->employee_id,
            'actor_user_id' => $actor->id,
            'batch_uuid' => $movement->batch_uuid,
            'type' => AssetMovement::TYPE_RETURN,
            'loan_due_date' => $previousLoanDueDate?->toDateString(),
            'note' => $this->buildUndoNote(
                label: 'movimiento',
                originalType: $movement->type,
                originalId: $movement->id,
                originalNote: $movement->note,
                batchUuid: $movement->batch_uuid,
            ),
        ]);

        AuditRecorder::record(
            action: AuditLog::ACTION_ASSET_RETURN,
            subjectType: AssetMovement::class,
            subjectId: $undoMovement->id,
            actorUserId: $actor->id,
            context: [
                'asset_id' => $asset->id,
                'employee_id' => $movement->employee_id,
                'reason' => 'undo',
                'movement_id' => $movement->id,
            ]
        );

        return [
            'type' => 'success',
            'title' => 'Movimiento deshecho',
            'message' => 'Se deshizo el préstamo del activo correctamente.',
            'context' => [
                'asset_id' => $asset->id,
                'product_id' => $asset->product_id,
            ],
        ];
    }

    /**
     * @return array{type: string, title: string, message: string, context: array<string, mixed>}
     *
     * @throws ValidationException
     */
    private function undoAssetReturn(Asset $asset, AssetMovement $movement, User $actor): array
    {
        if ($asset->status !== Asset::STATUS_AVAILABLE || $asset->current_employee_id !== null || $asset->loan_due_date !== null) {
            throw ValidationException::withMessages([
                'token' => 'No se puede deshacer porque el activo ya no está disponible.',
            ]);
        }

        AssetStatusTransitions::assertCanLoan($asset->status);

        $asset->status = Asset::STATUS_LOANED;
        $asset->current_employee_id = $movement->employee_id;
        $asset->loan_due_date = $movement->loan_due_date;
        $asset->save();

        $undoMovement = AssetMovement::create([
            'asset_id' => $asset->id,
            'employee_id' => $movement->employee_id,
            'actor_user_id' => $actor->id,
            'batch_uuid' => $movement->batch_uuid,
            'type' => AssetMovement::TYPE_LOAN,
            'loan_due_date' => $movement->loan_due_date?->toDateString(),
            'note' => $this->buildUndoNote(
                label: 'movimiento',
                originalType: $movement->type,
                originalId: $movement->id,
                originalNote: $movement->note,
                batchUuid: $movement->batch_uuid,
            ),
        ]);

        AuditRecorder::record(
            action: AuditLog::ACTION_ASSET_LOAN,
            subjectType: AssetMovement::class,
            subjectId: $undoMovement->id,
            actorUserId: $actor->id,
            context: [
                'asset_id' => $asset->id,
                'employee_id' => $movement->employee_id,
                'reason' => 'undo',
                'movement_id' => $movement->id,
            ]
        );

        return [
            'type' => 'success',
            'title' => 'Movimiento deshecho',
            'message' => 'Se deshizo la devolución del activo correctamente.',
            'context' => [
                'asset_id' => $asset->id,
                'product_id' => $asset->product_id,
            ],
        ];
    }

    private function buildUndoNote(
        string $label,
        string $originalType,
        int $originalId,
        string $originalNote,
        ?string $batchUuid = null,
    ): string {
        $prefix = "Deshacer {$label}: {$originalType} #{$originalId}";
        if (is_string($batchUuid) && trim($batchUuid) !== '') {
            $prefix .= " (batch {$batchUuid})";
        }

        $originalNote = trim($originalNote);
        $note = $originalNote !== '' ? "{$prefix}. Original: {$originalNote}" : "{$prefix}.";

        return Str::limit($note, 1000, '...');
    }
}
