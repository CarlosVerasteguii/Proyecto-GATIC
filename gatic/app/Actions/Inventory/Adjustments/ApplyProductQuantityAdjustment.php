<?php

namespace App\Actions\Inventory\Adjustments;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            /** @var Product $product */
            $product = Product::query()
                ->with('category')
                ->lockForUpdate()
                ->findOrFail($data['product_id']);

            if ($product->category?->is_serialized) {
                abort(404);
            }

            $before = ['qty_total' => $product->qty_total];
            $after = ['qty_total' => $data['new_qty']];

            $product->qty_total = $data['new_qty'];
            $product->save();

            $adjustment = InventoryAdjustment::create([
                'actor_user_id' => $data['actor_user_id'],
                'reason' => $data['reason'],
            ]);

            InventoryAdjustmentEntry::create([
                'inventory_adjustment_id' => $adjustment->id,
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'product_id' => $product->id,
                'asset_id' => null,
                'before' => $before,
                'after' => $after,
            ]);

            return $adjustment;
        });
    }
}
