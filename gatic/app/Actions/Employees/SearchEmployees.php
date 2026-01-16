<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use Illuminate\Support\Collection;

final class SearchEmployees
{
    /**
     * @return Collection<int, Employee>
     */
    public function execute(?string $search, int $limit = 10): Collection
    {
        $normalizedSearch = Employee::normalizeText($search);

        if ($normalizedSearch === null || mb_strlen($normalizedSearch) < 2) {
            return collect();
        }

        $escapedSearch = $this->escapeLike($normalizedSearch);

        $prefixResults = Employee::query()
            ->where(function ($query) use ($escapedSearch) {
                $query->whereRaw("rpe like ? escape '\\\\'", ["{$escapedSearch}%"])
                    ->orWhereRaw("name like ? escape '\\\\'", ["{$escapedSearch}%"]);
            })
            ->orderByRaw('CASE
                WHEN rpe = ? THEN 0
                WHEN rpe LIKE ? ESCAPE \'\\\\\' THEN 1
                WHEN name LIKE ? ESCAPE \'\\\\\' THEN 2
                ELSE 3
            END', [$normalizedSearch, "{$escapedSearch}%", "{$escapedSearch}%"])
            ->orderBy('rpe')
            ->limit($limit)
            ->get(['id', 'rpe', 'name', 'department']);

        if ($prefixResults->count() >= $limit) {
            return $prefixResults;
        }

        $remaining = $limit - $prefixResults->count();
        $excludeIds = $prefixResults->pluck('id')->all();

        $containsResults = Employee::query()
            ->when(
                $excludeIds !== [],
                static fn ($query) => $query->whereNotIn('id', $excludeIds),
            )
            ->where(function ($query) use ($escapedSearch) {
                $query->whereRaw("rpe like ? escape '\\\\'", ["%{$escapedSearch}%"])
                    ->orWhereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->orderByRaw('CASE
                WHEN rpe LIKE ? ESCAPE \'\\\\\' THEN 0
                WHEN name LIKE ? ESCAPE \'\\\\\' THEN 1
                ELSE 2
            END', ["%{$escapedSearch}%", "%{$escapedSearch}%"])
            ->orderBy('rpe')
            ->limit($remaining)
            ->get(['id', 'rpe', 'name', 'department']);

        return $prefixResults->concat($containsResults);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
