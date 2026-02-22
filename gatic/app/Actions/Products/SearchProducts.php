<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Collection;

final class SearchProducts
{
    /**
     * @return Collection<int, array{id: int, name: string, is_serialized: bool}>
     */
    public function execute(?string $search, int $limit = 10): Collection
    {
        $normalizedSearch = Product::normalizeName($search);

        if ($normalizedSearch === null || mb_strlen($normalizedSearch) < 2) {
            return collect();
        }

        $resolvedLimit = max(1, min($limit, 50));
        $escapedSearch = $this->escapeLike($normalizedSearch);

        $baseQuery = Product::query()
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at');

        $prefixResults = (clone $baseQuery)
            ->whereRaw("products.name like ? escape '\\\\'", ["{$escapedSearch}%"])
            ->orderByRaw('CASE
                WHEN products.name = ? THEN 0
                WHEN products.name LIKE ? ESCAPE \'\\\\\' THEN 1
                ELSE 2
            END', [$normalizedSearch, "{$escapedSearch}%"])
            ->orderBy('products.name')
            ->limit($resolvedLimit)
            ->get(['products.id', 'products.name', 'categories.is_serialized'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'is_serialized' => (bool) $row->getAttribute('is_serialized'),
            ]);

        if ($prefixResults->count() >= $resolvedLimit) {
            return $prefixResults;
        }

        $remaining = $resolvedLimit - $prefixResults->count();
        $excludeIds = $prefixResults->pluck('id')->all();

        $containsResults = (clone $baseQuery)
            ->when(
                $excludeIds !== [],
                static fn ($query) => $query->whereNotIn('products.id', $excludeIds),
            )
            ->whereRaw("products.name like ? escape '\\\\'", ["%{$escapedSearch}%"])
            ->orderBy('products.name')
            ->limit($remaining)
            ->get(['products.id', 'products.name', 'categories.is_serialized'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'is_serialized' => (bool) $row->getAttribute('is_serialized'),
            ]);

        return $prefixResults->concat($containsResults);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
