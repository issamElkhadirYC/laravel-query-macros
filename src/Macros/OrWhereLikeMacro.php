<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Macro: orWhereLike
 *
 * OR variant of whereLike - adds an OR condition for LIKE search.
 * Useful for searching across multiple columns or adding alternative conditions.
 *
 * @example
 * User::where('status', 'active')
 *     ->orWhereLike('name', 'john')
 *     ->orWhereLike('email', 'john')
 *     ->get();
 *
 * @why-use-this
 * Standard orWhere('column', 'LIKE', '%value%') doesn't handle case sensitivity
 * consistently. This provides the same cross-database benefits as whereLike.
 */
class OrWhereLikeMacro
{
    /**
     * Apply the orWhereLike macro to the query builder.
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
                'sqlite' => $query->orWhereRaw("{$wrappedColumn} GLOB ?", [str_replace(['%', '_'], ['*', '?'], $searchValue)]),
                default => $query->orWhere($column, 'LIKE', $searchValue),
            };
        }

        // Case-insensitive search - database-specific implementation
        return match ($driver) {
            'mysql' => $query->orWhereRaw("LOWER({$wrappedColumn}) LIKE ?", [strtolower($searchValue)]),
            'pgsql' => $query->orWhereRaw("{$wrappedColumn} ILIKE ?", [$searchValue]),
            'sqlite' => $query->orWhereRaw("LOWER({$wrappedColumn}) LIKE LOWER(?)", [$searchValue]),
            default => $query->orWhereRaw("LOWER({$wrappedColumn}) LIKE ?", [strtolower($searchValue)]),
        };
    }
}

