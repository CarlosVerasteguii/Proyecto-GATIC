<?php

namespace App\Support\Catalogs;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CatalogUsage
{
    /**
     * @var array<string, array<int, array{table: string, column: string}>>
     */
    private static array $referencesCache = [];

    /**
     * @var array<string, string>
     */
    private static array $referenceTableLabels = [
        'assets' => 'Activos',
        'contracts' => 'Contratos',
        'products' => 'Productos',
    ];

    public static function isInUse(string $catalogTable, int $catalogId): bool
    {
        foreach (self::foreignKeyReferencesToCached($catalogTable) as $reference) {
            if (DB::table($reference['table'])->where($reference['column'], $catalogId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, int>
     */
    public static function usageCounts(string $catalogTable, int $catalogId): array
    {
        $counts = [];

        foreach (self::foreignKeyReferencesToCached($catalogTable) as $reference) {
            $count = (int) DB::table($reference['table'])
                ->where($reference['column'], $catalogId)
                ->count();

            if ($count < 1) {
                continue;
            }

            $counts[$reference['table']] = ($counts[$reference['table']] ?? 0) + $count;
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     */
    public static function formatUsageCounts(array $counts): string
    {
        $parts = [];

        foreach ($counts as $table => $count) {
            $label = self::$referenceTableLabels[$table] ?? $table;
            $parts[] = $label.' ('.number_format($count).')';
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<int, array{table: string, column: string}>
     */
    private static function foreignKeyReferencesToCached(string $catalogTable): array
    {
        if (app()->environment('testing')) {
            return self::foreignKeyReferencesTo($catalogTable);
        }

        if (array_key_exists($catalogTable, self::$referencesCache)) {
            return self::$referencesCache[$catalogTable];
        }

        try {
            self::$referencesCache[$catalogTable] = self::foreignKeyReferencesTo($catalogTable);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "No se pudo determinar si el catálogo '{$catalogTable}' está en uso.",
                previous: $exception
            );
        }

        return self::$referencesCache[$catalogTable];
    }

    /**
     * @return array<int, array{table: string, column: string}>
     */
    private static function foreignKeyReferencesTo(string $catalogTable): array
    {
        $rows = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select([
                'TABLE_NAME as table',
                'COLUMN_NAME as column',
            ])
            ->whereRaw('REFERENCED_TABLE_SCHEMA = database()')
            ->where('REFERENCED_TABLE_NAME', $catalogTable)
            ->where('REFERENCED_COLUMN_NAME', 'id')
            ->orderBy('TABLE_NAME')
            ->orderBy('COLUMN_NAME')
            ->get();

        return $rows->map(static fn ($row) => [
            'table' => (string) $row->table,
            'column' => (string) $row->column,
        ])->all();
    }
}
