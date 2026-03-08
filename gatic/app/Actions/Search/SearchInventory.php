<?php

namespace App\Actions\Search;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchInventory
{
    private const FULLTEXT_MIN_TOKEN_CHARS = 4;

    /**
     * Execute unified inventory search.
     *
     * @return array{products: Collection<int, Product>, assets: Collection<int, Asset>, exactMatch: Asset|null}
     */
    public function execute(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [
                'products' => collect(),
                'assets' => collect(),
                'exactMatch' => null,
            ];
        }

        // 1. Try exact match by asset_tag first (globally unique)
        $exactByAssetTag = $this->findExactAssetByTag($query);
        if ($exactByAssetTag !== null) {
            return [
                'products' => collect(),
                'assets' => collect([$exactByAssetTag]),
                'exactMatch' => $exactByAssetTag,
            ];
        }

        // 2. Try exact match by serial
        $exactBySerialResults = $this->findExactAssetsBySerial($query);
        if ($exactBySerialResults->count() === 1) {
            // Single match - can do direct jump
            return [
                'products' => collect(),
                'assets' => $exactBySerialResults,
                'exactMatch' => $exactBySerialResults->first(),
            ];
        }

        if ($exactBySerialResults->isNotEmpty()) {
            // Multiple matches - ambiguous, show list (no exact match)
            return [
                'products' => collect(),
                'assets' => $exactBySerialResults,
                'exactMatch' => null,
            ];
        }

        // 3. Partial search: products by name, assets by serial/asset_tag
        $products = $this->searchProductsByName($query);
        $assets = $this->searchAssetsBySerialOrTag($query);

        return [
            'products' => $products,
            'assets' => $assets,
            'exactMatch' => null,
        ];
    }

    /**
     * Find exact match by asset_tag (case-insensitive, globally unique).
     */
    private function findExactAssetByTag(string $query): ?Asset
    {
        $normalizedTag = Asset::normalizeAssetTag($query);

        if ($normalizedTag === null) {
            return null;
        }

        return Asset::query()
            ->with(['product', 'location', 'currentEmployee'])
            ->where('asset_tag', $normalizedTag)
            ->first();
    }

    /**
     * Find exact matches by serial (case-sensitive after normalization).
     * Returns all assets with exact serial match (could be multiple across products).
     *
     * @return Collection<int, Asset>
     */
    private function findExactAssetsBySerial(string $query): Collection
    {
        $normalizedSerial = Asset::normalizeSerial($query);

        if ($normalizedSerial === null) {
            return collect();
        }

        return Asset::query()
            ->with(['product', 'location', 'currentEmployee'])
            ->where('serial', $normalizedSerial)
            ->orderBy('serial')
            ->get();
    }

    /**
     * Search products by name (partial match).
     *
     * @return Collection<int, Product>
     */
    private function searchProductsByName(string $query): Collection
    {
        $normalizedName = Product::normalizeName($query);

        if ($normalizedName === null) {
            return collect();
        }

        $tokens = preg_split('/\\s+/u', $normalizedName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            return collect();
        }

        $fullTextTokens = array_values(array_filter($tokens, function (string $token): bool {
            return mb_strlen($token) >= self::FULLTEXT_MIN_TOKEN_CHARS;
        }));

        $productsQuery = $this->buildProductsQuery();

        if (! app()->environment('testing') && $fullTextTokens !== []) {
            $booleanQuery = $this->buildFullTextBooleanQuery($fullTextTokens);

            return $productsQuery
                ->whereRaw('match(products.name) against (? in boolean mode) > 0', [$booleanQuery])
                ->orderByRaw('match(products.name) against (? in boolean mode) desc', [$booleanQuery])
                ->orderBy('products.name')
                ->limit(20)
                ->get();
        }

        // Fallback for short tokens (< ft_min_word_len by default).
        // Keep it index-friendly (no leading wildcard) by matching from the start.
        $escapedTokens = array_map(fn (string $token): string => $this->escapeLike($token), $tokens);
        $likePattern = implode('%', $escapedTokens).'%';

        return $productsQuery
            ->whereRaw("name like ? escape '\\\\'", [$likePattern])
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * @param  list<string>  $tokens
     */
    private function buildFullTextBooleanQuery(array $tokens): string
    {
        $terms = [];

        foreach ($tokens as $token) {
            $token = preg_replace('/[^\\pL\\pN]+/u', ' ', $token) ?? '';
            $token = trim(preg_replace('/\\s+/u', ' ', $token) ?? '');

            if ($token === '') {
                continue;
            }

            $pieces = preg_split('/\\s+/u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($pieces as $piece) {
                if (! array_key_exists($piece, $terms)) {
                    $terms[$piece] = '+'.$piece.'*';
                }
            }
        }

        return implode(' ', array_values($terms));
    }

    /**
     * Search assets by serial or asset_tag (partial match).
     *
     * @return Collection<int, Asset>
     */
    private function searchAssetsBySerialOrTag(string $query): Collection
    {
        $escapedSearch = $this->escapeLike(trim($query));

        if ($escapedSearch === '') {
            return collect();
        }

        // Prefix match to keep it index-friendly (assets.serial + assets.asset_tag).
        $likePattern = "{$escapedSearch}%";

        return Asset::query()
            ->with(['product', 'location', 'currentEmployee'])
            ->where(function ($q) use ($likePattern) {
                $q->whereRaw("serial like ? escape '\\\\'", [$likePattern])
                    ->orWhereRaw("asset_tag like ? escape '\\\\'", [$likePattern]);
            })
            ->orderBy('serial')
            ->limit(20)
            ->get();
    }

    /**
     * Escape LIKE wildcards to prevent SQL injection via pattern characters.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function buildProductsQuery()
    {
        return Product::query()
            ->select('products.*')
            ->leftJoinSub($this->buildAssetCountsSubquery(), 'asset_counts', function ($join) {
                $join->on('asset_counts.product_id', '=', 'products.id');
            })
            ->addSelect(DB::raw('coalesce(asset_counts.assets_total, 0) as assets_total'))
            ->addSelect(DB::raw('coalesce(asset_counts.assets_unavailable, 0) as assets_unavailable'))
            ->with(['category', 'brand']);
    }

    private function buildAssetCountsSubquery()
    {
        $unavailableStatuses = Asset::UNAVAILABLE_STATUSES;
        $unavailablePlaceholders = implode(',', array_fill(0, count($unavailableStatuses), '?'));

        return Asset::query()
            ->select('assets.product_id')
            ->selectRaw(
                'sum(case when assets.status <> ? then 1 else 0 end) as assets_total',
                [Asset::STATUS_RETIRED]
            )
            ->selectRaw(
                "sum(case when assets.status in ($unavailablePlaceholders) then 1 else 0 end) as assets_unavailable",
                $unavailableStatuses
            )
            ->groupBy('assets.product_id');
    }
}
