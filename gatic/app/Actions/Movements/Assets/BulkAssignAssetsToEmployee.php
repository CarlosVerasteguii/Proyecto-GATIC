<?php

declare(strict_types=1);

namespace App\Actions\Movements\Assets;

use App\Exceptions\AssetTransitionException;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AuditLog;
use App\Support\Assets\AssetStatusTransitions;
use App\Support\Audit\AuditRecorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkAssignAssetsToEmployee
{
    /**
     * @param  array{asset_ids: list<int>, employee_id: int, note: string, actor_user_id: int}  $data
     * @return array{batch_uuid: string, movements: Collection<int, AssetMovement>}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): array
    {
        $maxAssets = (int) config('gatic.inventory.bulk_actions.max_assets', 50);

        Validator::make($data, [
            'asset_ids' => ['required', 'array', 'min:1', 'max:'.$maxAssets],
            'asset_ids.*' => ['required', 'integer', 'distinct', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ], [
            'asset_ids.required' => 'Debe seleccionar al menos un activo.',
            'asset_ids.array' => 'La selección de activos es inválida.',
            'asset_ids.min' => 'Debe seleccionar al menos un activo.',
            'asset_ids.max' => "El máximo permitido es {$maxAssets} activos.",
            'asset_ids.*.required' => 'La selección de activos es inválida.',
            'asset_ids.*.integer' => 'La selección de activos es inválida.',
            'asset_ids.*.distinct' => 'La selección contiene activos duplicados.',
            'asset_ids.*.exists' => 'Uno o más activos seleccionados no existen.',
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'note.required' => 'La nota es obligatoria.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
            'note.max' => 'La nota no puede exceder :max caracteres.',
        ])->validate();

        /** @var list<int> $assetIds */
        $assetIds = array_values($data['asset_ids']);

        return DB::transaction(function () use ($assetIds, $data): array {
            $assets = Asset::query()
                ->whereIn('id', $assetIds)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($assets->count() !== count($assetIds)) {
                throw ValidationException::withMessages([
                    'asset_ids' => ['Uno o más activos seleccionados no existen.'],
                ]);
            }

            foreach ($assets as $asset) {
                try {
                    AssetStatusTransitions::assertCanAssign($asset->status);
                } catch (AssetTransitionException $e) {
                    $serial = $asset->serial !== '' ? $asset->serial : (string) $asset->id;

                    throw ValidationException::withMessages([
                        'asset_ids' => ["No se puede asignar el activo \"{$serial}\" (ID {$asset->id}): {$e->getMessage()}"],
                    ]);
                }
            }

            $batchUuid = (string) Str::uuid();
            $movements = collect();

            foreach ($assets as $asset) {
                $asset->status = Asset::STATUS_ASSIGNED;
                $asset->current_employee_id = $data['employee_id'];
                $asset->save();

                $movement = AssetMovement::create([
                    'asset_id' => $asset->id,
                    'employee_id' => $data['employee_id'],
                    'actor_user_id' => $data['actor_user_id'],
                    'batch_uuid' => $batchUuid,
                    'type' => AssetMovement::TYPE_ASSIGN,
                    'note' => $data['note'],
                ]);

                // Best-effort audit (mismo patrón que AssignAssetToEmployee)
                AuditRecorder::record(
                    action: AuditLog::ACTION_ASSET_ASSIGN,
                    subjectType: AssetMovement::class,
                    subjectId: $movement->id,
                    actorUserId: $data['actor_user_id'],
                    context: [
                        'asset_id' => $asset->id,
                        'employee_id' => $data['employee_id'],
                    ]
                );

                $movements->push($movement);
            }

            return [
                'batch_uuid' => $batchUuid,
                'movements' => $movements,
            ];
        });
    }
}
