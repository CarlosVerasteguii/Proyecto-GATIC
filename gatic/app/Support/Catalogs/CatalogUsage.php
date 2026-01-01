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
