<?php

namespace App\Actions\Inventory\Adjustments;

use App\Actions\Inventory\Products\LockQuantityProduct;
use App\Models\AuditLog;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use App\Support\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApplyProductQuantityAdjustment
{
    /**
     * @param  array{product_id: int, new_qty: int, reason: string, actor_user_id: int}  $data
     */
    public function execute(array $data): InventoryAdjustment
    {
        Validator::make($data, [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'new_qty' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ])->validate();

        return DB::transaction(function () use ($data): InventoryAdjustment {
            $product = (new LockQuantityProduct)->execute($data['product_id']);

            $before = ['qty_total' => $product->qty_total];
            $after = ['qty_total' => $data['new_qty']];

            $product->qty_total = $data['new_qty'];
            $product->save();

            $adjustment = InventoryAdjustment::create([
                'actor_user_id' => $data['actor_user_id'],
                'reason' => $data['reason'],
            ]);

            $entry = InventoryAdjustmentEntry::create([
                'inventory_adjustment_id' => $adjustment->id,
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'product_id' => $product->id,
                'asset_id' => null,
                'before' => $before,
                'after' => $after,
            ]);

            // Best-effort audit (AC1, AC2, AC5)
            AuditRecorder::record(
                action: AuditLog::ACTION_INVENTORY_ADJUSTMENT,
                subjectType: InventoryAdjustmentEntry::class,
                subjectId: $entry->id,
                actorUserId: $data['actor_user_id'],
                context: [
                    'product_id' => $product->id,
                    'inventory_adjustment_id' => $adjustment->id,
                    'reason' => $data['reason'],
                    'summary' => "qty_total: {$before['qty_total']} -> {$after['qty_total']}",
                ]
            );

            return $adjustment;
        });
    }
}
