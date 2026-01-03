<?php

namespace App\Actions\Inventory\Adjustments;

use App\Models\Asset;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApplyAssetAdjustment
{
    /**
     * @param  array{asset_id: int, new_status: string, new_location_id: int, reason: string, actor_user_id: int}  $data
     */
    public function execute(array $data): InventoryAdjustment
    {
        Validator::make($data, [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'new_status' => ['required', 'string', Rule::in(Asset::STATUSES)],
            'new_location_id' => ['required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ])->validate();

        return DB::transaction(function () use ($data): InventoryAdjustment {
            /** @var Asset $asset */
            $asset = Asset::query()
                ->lockForUpdate()
                ->findOrFail($data['asset_id']);

            $before = [
                'status' => $asset->status,
                'location_id' => $asset->location_id,
            ];

            $after = [
                'status' => $data['new_status'],
                'location_id' => $data['new_location_id'],
            ];

            $asset->status = $data['new_status'];
            $asset->location_id = $data['new_location_id'];
            $asset->save();

            $adjustment = InventoryAdjustment::create([
                'actor_user_id' => $data['actor_user_id'],
                'reason' => $data['reason'],
            ]);

            InventoryAdjustmentEntry::create([
                'inventory_adjustment_id' => $adjustment->id,
                'subject_type' => Asset::class,
                'subject_id' => $asset->id,
                'product_id' => $asset->product_id,
                'asset_id' => $asset->id,
                'before' => $before,
                'after' => $after,
            ]);

            return $adjustment;
        });
    }
}
