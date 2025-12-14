<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Macro: whereJsonContainsAny
 *
 * Check if a JSON column contains ANY of the provided values.
 * Useful for filtering records where JSON arrays/objects contain at least one matching value.
 *
 * @example
 * // Find users with any of these tags
 * User::whereJsonContainsAny('tags', ['admin', 'moderator', 'editor'])->get();
 *
 * // Find products in any of these categories
 * Product::whereJsonContainsAny('categories', [1, 2, 3])->get();
 *
 * @why-use-this
 * Laravel's whereJsonContains() only checks for a single value. This macro allows
 * checking for multiple values in a single, readable query.
 */
class WhereJsonContainsAnyMacro
{
    /**
     * Apply the whereJsonContainsAny macro to the query builder.
     *
     * @param Builder $query The query builder instance
     * @param string $column The JSON column to search in
     * @param array $values Array of values to check for (at least one must exist)
     * @return Builder
     */
    public static function apply(Builder $query, string $column, array $values): Builder
    {
        if (empty($values)) {
            // If no values provided, return query that matches nothing
            return $query->whereRaw('1 = 0');
        }

        $driver = DB::getDriverName();

        // Database-specific implementation
        return match ($driver) {
            'mysql' => self::applyForMySQL($query, $column, $values),
            'pgsql' => self::applyForPostgreSQL($query, $column, $values),
            'sqlite' => self::applyForSQLite($query, $column, $values),
            default => self::applyForMySQL($query, $column, $values),
        };
    }

    /**
     * Apply for MySQL database.
     */
    private static function applyForMySQL(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        $conditions = collect($values)->map(function () use ($wrappedColumn) {
            return "JSON_CONTAINS({$wrappedColumn}, ?)";
        })->implode(' OR ');

        $bindings = [];
        foreach ($values as $value) {
            $bindings[] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $query->whereRaw("({$conditions})", $bindings);
    }

    /**
     * Apply for PostgreSQL database.
     */
    private static function applyForPostgreSQL(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        $conditions = collect($values)->map(function () use ($wrappedColumn) {
            return "{$wrappedColumn}::jsonb @> ?::jsonb";
        })->implode(' OR ');

        $bindings = [];
        foreach ($values as $value) {
            $bindings[] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $query->whereRaw("({$conditions})", $bindings);
    }

    /**
     * Apply for SQLite database.
     */
    private static function applyForSQLite(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        // SQLite json_each extracts values from JSON arrays
        // json_each.value returns the actual value (string without quotes, number as-is)
        // We compare the value directly with our search values
        $conditions = collect($values)->map(function () use ($wrappedColumn) {
            return "EXISTS (SELECT 1 FROM json_each({$wrappedColumn}) WHERE json_each.value = ?)";
        })->implode(' OR ');

        $bindings = [];
        foreach ($values as $value) {
            // json_each.value returns the actual value, so for strings we use the string directly
            // For numbers, we use the number directly
            if (is_string($value)) {
                $bindings[] = $value;
            } else {
                // For non-strings, json_each returns them as their type, so we use the value directly
                $bindings[] = $value;
            }
        }

        return $query->whereRaw("({$conditions})", $bindings);
    }
}

