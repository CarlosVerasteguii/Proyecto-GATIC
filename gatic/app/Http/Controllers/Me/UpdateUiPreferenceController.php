<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Support\Settings\UserSettingsStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class UpdateUiPreferenceController extends Controller
{
    public function __invoke(Request $request, UserSettingsStore $store): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => ['required', 'string', 'max:120'],
            'value' => ['present'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        try {
            $store->setForUser((int) $user->id, (string) $request->input('key'), $request->input('value'), (int) $user->id);
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => 'Preferencia UI inválida.',
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
