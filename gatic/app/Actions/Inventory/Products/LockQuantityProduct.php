<?php

declare(strict_types=1);

namespace App\Actions\Inventory\Products;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class LockQuantityProduct
{
    /**
     * @throws ValidationException
     */
    public function execute(int $productId): Product
    {
        /** @var Product $product */
        $product = Product::query()
            ->with('category')
            ->lockForUpdate()
            ->findOrFail($productId);

        if ($product->category?->is_serialized) {
            throw ValidationException::withMessages([
                'product_id' => [
                    'Este producto es serializado. Esta operación solo aplica a productos no serializados.',
                ],
            ]);
        }

        return $product;
    }
}

