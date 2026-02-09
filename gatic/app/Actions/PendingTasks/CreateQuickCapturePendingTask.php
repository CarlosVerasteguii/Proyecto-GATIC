<?php

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\PendingTask;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateQuickCapturePendingTask
{
    /**
     * @param  array{
     *   type: string,
     *   description?: string|null,
     *   creator_user_id: int,
     *   payload: array<string, mixed>
     * }  $data
     */
    public function execute(array $data): PendingTask
    {
        Validator::make($data, [
            'type' => ['required', 'string', Rule::in([
                PendingTaskType::StockIn->value,
                PendingTaskType::Retirement->value,
            ])],
            'description' => ['nullable', 'string', 'max:5000'],
            'creator_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'payload' => ['required', 'array'],
        ])->validate();

        return PendingTask::create([
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'payload' => $data['payload'],
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $data['creator_user_id'],
        ]);
    }
}
