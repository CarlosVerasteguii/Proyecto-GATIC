<?php

declare(strict_types=1);

namespace App\Actions\Movements\Undo;

use App\Models\UndoToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateUndoToken
{
    /**
     * @param  array{actor_user_id: int, movement_kind: string, movement_id?: int|null, batch_uuid?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): UndoToken
    {
        Validator::make($data, [
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'movement_kind' => ['required', 'string', Rule::in(UndoToken::KINDS)],
            'movement_id' => ['nullable', 'integer', 'min:1'],
            'batch_uuid' => ['nullable', 'uuid'],
        ], [
            'actor_user_id.required' => 'El usuario es obligatorio.',
            'actor_user_id.exists' => 'El usuario no existe.',
            'movement_kind.required' => 'El tipo de movimiento es obligatorio.',
            'movement_kind.in' => 'El tipo de movimiento no es válido.',
            'movement_id.integer' => 'El movimiento no es válido.',
            'batch_uuid.uuid' => 'El batch no es válido.',
        ])->validate();

        $movementId = $data['movement_id'] ?? null;
        $batchUuid = $data['batch_uuid'] ?? null;

        if (($movementId === null && $batchUuid === null) || ($movementId !== null && $batchUuid !== null)) {
            throw ValidationException::withMessages([
                'token' => 'Debes especificar movement_id o batch_uuid (solo uno).',
            ]);
        }

        $windowS = (int) config('gatic.inventory.undo.window_s', 10);
        $windowS = $windowS > 0 ? $windowS : 10;

        return UndoToken::create([
            'id' => (string) Str::uuid(),
            'actor_user_id' => $data['actor_user_id'],
            'movement_kind' => $data['movement_kind'],
            'movement_id' => $movementId,
            'batch_uuid' => $batchUuid,
            'expires_at' => now()->addSeconds($windowS),
        ]);
    }
}
