<?php

namespace App\Actions\Suppliers;

use App\Models\Supplier;
use Illuminate\Support\Collection;

final class SearchSuppliers
{
    /**
     * @return Collection<int, Supplier>
     */
    public function execute(?string $search, int $limit = 10): Collection
    {
        $normalizedSearch = Supplier::normalizeName($search);

        if ($normalizedSearch === null || mb_strlen($normalizedSearch) < 2) {
            return collect();
        }

        $escapedSearch = $this->escapeLike($normalizedSearch);

        $prefixResults = Supplier::query()
            ->whereRaw("name like ? escape '\\\\'", ["{$escapedSearch}%"])
            ->orderByRaw('CASE
                WHEN name = ? THEN 0
                WHEN name LIKE ? ESCAPE \'\\\\\' THEN 1
                ELSE 2
            END', [$normalizedSearch, "{$escapedSearch}%"])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        if ($prefixResults->count() >= $limit) {
            return $prefixResults;
        }

        $remaining = $limit - $prefixResults->count();
        $excludeIds = $prefixResults->pluck('id')->all();

        $containsResults = Supplier::query()
            ->when(
                $excludeIds !== [],
                static fn ($query) => $query->whereNotIn('id', $excludeIds),
            )
            ->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"])
            ->orderBy('name')
            ->limit($remaining)
            ->get(['id', 'name']);

        return $prefixResults->concat($containsResults);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
