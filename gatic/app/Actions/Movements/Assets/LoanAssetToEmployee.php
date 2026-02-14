<?php

declare(strict_types=1);

namespace App\Actions\Movements\Assets;

use App\Exceptions\AssetTransitionException;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AuditLog;
use App\Support\Assets\AssetStatusTransitions;
use App\Support\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LoanAssetToEmployee
{
    /**
     * @param  array{asset_id: int, employee_id: int, note: string, actor_user_id: int, loan_due_date?: string|null}  $data
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): AssetMovement
    {
        Validator::make($data, [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'loan_due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ], [
            'asset_id.required' => 'El activo es obligatorio.',
            'asset_id.exists' => 'El activo seleccionado no existe.',
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'note.required' => 'La nota es obligatoria.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
            'note.max' => 'La nota no puede exceder :max caracteres.',
            'loan_due_date.date_format' => 'La fecha de vencimiento debe tener el formato YYYY-MM-DD.',
            'loan_due_date.after_or_equal' => 'La fecha de vencimiento no puede ser en el pasado.',
        ])->validate();

        return DB::transaction(function () use ($data): AssetMovement {
            /** @var Asset $asset */
            $asset = Asset::query()
                ->lockForUpdate()
                ->findOrFail($data['asset_id']);

            try {
                AssetStatusTransitions::assertCanLoan($asset->status);
            } catch (AssetTransitionException $e) {
                throw $e->toValidationException('asset_id');
            }

            $asset->status = Asset::STATUS_LOANED;
            $asset->current_employee_id = $data['employee_id'];
            $asset->loan_due_date = $data['loan_due_date'] ?? null;
            $asset->save();

            $movement = AssetMovement::create([
                'asset_id' => $asset->id,
                'employee_id' => $data['employee_id'],
                'actor_user_id' => $data['actor_user_id'],
                'type' => AssetMovement::TYPE_LOAN,
                'loan_due_date' => $data['loan_due_date'] ?? null,
                'note' => $data['note'],
            ]);

            // Auditoría best-effort
            AuditRecorder::record(
                action: AuditLog::ACTION_ASSET_LOAN,
                subjectType: AssetMovement::class,
                subjectId: $movement->id,
                actorUserId: $data['actor_user_id'],
                context: [
                    'asset_id' => $asset->id,
                    'employee_id' => $data['employee_id'],
                    'loan_due_date' => $data['loan_due_date'] ?? null,
                ]
            );

            return $movement;
        });
    }
}
