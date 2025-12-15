<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Macros;

use Illuminate\Database\Eloquent\Builder;

class WhereJsonContainsAnyMacro
{
    /**
     * Add a where clause that checks if a JSON column contains ANY of the provided values.
     * 
     * This macro works with JSON arrays and checks if at least one value from the provided
     * array exists in the JSON column.
     * 
     * Performance note: JSON operations can be slower than regular column searches.
     * Consider adding JSON indexes for frequently queried columns.
     *
     * @param Builder $query
     * @param string $column The JSON column name
     * @param array $values Array of values to search for (any match will return the record)
     * @return Builder
     */
    public static function apply(Builder $query, string $column, array $values): Builder
    {
        // Return no results if empty array provided
        if (empty($values)) {
            return $query->whereRaw('1 = 0');
        }

        $connection = $query->getConnection();
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => self::applyForMySQL($query, $column, $values),
            'pgsql' => self::applyForPostgreSQL($query, $column, $values),
            'sqlite' => self::applyForSQLite($query, $column, $values),
            'sqlsrv' => self::applyForSQLServer($query, $column, $values),
            default => self::applyForMySQL($query, $column, $values),
        };
    }

    /**
     * MySQL/MariaDB implementation using JSON_CONTAINS with proper array wrapping.
     */
    private static function applyForMySQL(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        // Build OR conditions for each value
        $conditions = [];
        $bindings = [];
        
        foreach ($values as $value) {
            // JSON_CONTAINS needs the search value as a JSON-encoded string
            // For checking if array contains value: JSON_CONTAINS(column, '"value"')
            $conditions[] = "JSON_CONTAINS({$wrappedColumn}, ?)";
            $bindings[] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        $conditionsString = implode(' OR ', $conditions);
        
        return $query->whereRaw("({$conditionsString})", $bindings);
    }

    /**
     * PostgreSQL implementation using the ?| (key exists any) operator.
     * 
     * Note: This works best when the JSON column contains an array of scalar values.
     * For nested structures, different operators may be needed.
     */
    private static function applyForPostgreSQL(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        // Convert values to strings for the ?| operator
        $stringValues = array_map(function ($value) {
            return is_string($value) ? $value : json_encode($value);
        }, $values);
        
        // PostgreSQL ?| operator checks if any of the provided keys/values exist
        // We need to use the array constructor syntax
        $placeholders = implode(', ', array_fill(0, count($stringValues), '?'));
        
        return $query->whereRaw(
            "{$wrappedColumn}::jsonb ?| array[{$placeholders}]::text[]",
            $stringValues
        );
    }

    /**
     * SQLite implementation using json_each to iterate through array elements.
     * 
     * SQLite's JSON support is more limited, so we use json_each to iterate
     * through the array and check for matches.
     */
    private static function applyForSQLite(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        $conditions = [];
        $bindings = [];
        
        foreach ($values as $value) {
            if (is_string($value)) {
                // For strings, compare directly
                $conditions[] = "EXISTS (
                    SELECT 1 FROM json_each({$wrappedColumn}) 
                    WHERE json_each.value = ?
                )";
                $bindings[] = $value;
            } elseif (is_int($value) || is_float($value)) {
                // For numbers, use json_each.value which preserves type
                $conditions[] = "EXISTS (
                    SELECT 1 FROM json_each({$wrappedColumn}) 
                    WHERE json_each.value = ?
                )";
                $bindings[] = $value;
            } elseif (is_bool($value)) {
                // For booleans, SQLite stores as 0/1 in json_each
                $conditions[] = "EXISTS (
                    SELECT 1 FROM json_each({$wrappedColumn}) 
                    WHERE json_each.value = ?
                )";
                $bindings[] = $value ? 1 : 0;
            } else {
                // For other types, compare as JSON
                $conditions[] = "EXISTS (
                    SELECT 1 FROM json_each({$wrappedColumn}) 
                    WHERE json_each.value = json(?)
                )";
                $bindings[] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
        
        $conditionsString = implode(' OR ', $conditions);
        
        return $query->whereRaw("({$conditionsString})", $bindings);
    }

    /**
     * SQL Server implementation using OPENJSON.
     * 
     * SQL Server 2016+ has JSON support via OPENJSON function.
     */
    private static function applyForSQLServer(Builder $query, string $column, array $values): Builder
    {
        $grammar = $query->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);
        
        $conditions = [];
        $bindings = [];
        
        foreach ($values as $value) {
            // Use OPENJSON to parse the JSON array and check if value exists
            $conditions[] = "EXISTS (
                SELECT 1 FROM OPENJSON({$wrappedColumn}) 
                WHERE value = ?
            )";
            $bindings[] = is_string($value) ? $value : json_encode($value);
        }
        
        $conditionsString = implode(' OR ', $conditions);
        
        return $query->whereRaw("({$conditionsString})", $bindings);
    }
}