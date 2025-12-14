<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Macro: whereLike
 *
 * Provides cross-database LIKE search functionality with case sensitivity control.
 * Handles differences between MySQL, PostgreSQL, and SQLite.
 *
 * @example
 * User::whereLike('name', 'john')->get();
 * User::whereLike('email', 'gmail', caseSensitive: true)->get();
 *
 * @why-use-this
 * Standard where('column', 'LIKE', '%value%') doesn't handle case sensitivity
 * consistently across databases. This macro provides a unified API.
 */
class WhereLikeMacro
{
    /**
     * Apply the whereLike macro to the query builder.
     *
     * @param Builder $query The query builder instance
     * @param string $column The column to search in
     * @param string $value The search value (will be wrapped with %)
     * @param bool $caseSensitive Whether the search should be case-sensitive
     * @return Builder
     */
    public static function apply(Builder $query, string $column, string $value, bool $caseSensitive = false): Builder
    {
        $driver = DB::getDriverName();
        $searchValue = '%' . $value . '%';
        
        // Get the grammar to properly wrap column names
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);

        // Case-sensitive search - database-specific implementation
        if ($caseSensitive) {
            return match ($driver) {
                'sqlite' => $query->whereRaw("{$wrappedColumn} GLOB ?", [str_replace(['%', '_'], ['*', '?'], $searchValue)]),
                default => $query->where($column, 'LIKE', $searchValue),
            };
        }

        // Case-insensitive search - database-specific implementation
        return match ($driver) {
            'mysql' => $query->whereRaw("LOWER({$wrappedColumn}) LIKE ?", [strtolower($searchValue)]),
            'pgsql' => $query->whereRaw("{$wrappedColumn} ILIKE ?", [$searchValue]),
            'sqlite' => $query->whereRaw("LOWER({$wrappedColumn}) LIKE LOWER(?)", [$searchValue]),
            default => $query->whereRaw("LOWER({$wrappedColumn}) LIKE ?", [strtolower($searchValue)]),
        };
    }
}

