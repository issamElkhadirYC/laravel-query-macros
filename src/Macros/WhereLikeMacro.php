<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WhereLikeMacro
{
    /**
     * Add a "where like" clause with cross-database support.
     * 
     * Performance note: Case-insensitive searches using LOWER() cannot utilize indexes.
     * For large datasets, consider full-text search indexes instead.
     *
     * @param Builder $query
     * @param string $column Column name (can be qualified: 'users.email')
     * @param string $value Search value (% wildcards added automatically)
     * @param bool $caseSensitive Case-sensitive search (default: false)
     * @param bool $escapeWildcards Escape % and _ in search value (default: false)
     * @return Builder
     */
    public static function apply(
        Builder $query, 
        string $column, 
        string $value, 
        bool $caseSensitive = false,
        bool $escapeWildcards = false
    ): Builder {
        $connection = $query->getConnection();
        $driver = $connection->getDriverName();
        $grammar = $connection->getQueryGrammar();
        
        // Escape wildcards if requested
        if ($escapeWildcards) {
            $value = self::escapeWildcards($value);
        }
        
        $searchValue = '%' . $value . '%';
        $wrappedColumn = $grammar->wrap($column);

        if ($caseSensitive) {
            return match ($driver) {
                'pgsql' => $query->where($column, 'LIKE', $searchValue),
                'sqlite' => $query->whereRaw("{$wrappedColumn} LIKE ? COLLATE BINARY", [$searchValue]),
                'sqlsrv' => $query->where($column, 'LIKE', $searchValue),
                'mysql', 'mariadb' => $query->where($column, 'LIKE BINARY', $searchValue),
                default => $query->where($column, 'LIKE', $searchValue),
            };
        }

        // Case-insensitive search
        return match ($driver) {
            'pgsql' => $query->where($column, 'ILIKE', $searchValue),
            'mysql', 'mariadb' => $query->where(DB::raw("LOWER({$wrappedColumn})"), 'LIKE', strtolower($searchValue)),
            'sqlite' => $query->where(DB::raw("LOWER({$wrappedColumn})"), 'LIKE', strtolower($searchValue)),
            'sqlsrv' => $query->where(DB::raw("LOWER({$wrappedColumn})"), 'LIKE', strtolower($searchValue)),
            default => $query->where(DB::raw("LOWER({$wrappedColumn})"), 'LIKE', strtolower($searchValue)),
        };
    }

    /**
     * Escape SQL wildcards (%, _) so they're treated as literal characters.
     *
     * @param string $value
     * @return string
     */
    private static function escapeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
