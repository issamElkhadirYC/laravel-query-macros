# Laravel Query Macros

A collection of useful, production-ready Eloquent query macros that solve common pain points in Laravel applications.

## Requirements

- PHP 8.2+
- Laravel 10+ or 11+

## Installation

```bash
composer require laravel-query-macros/laravel-query-macros
```

The service provider will be auto-discovered. If you're using Laravel < 5.5, manually register it in `config/app.php`:

```php
'providers' => [
    // ...
    LaravelQueryMacros\QueryMacros\QueryMacrosServiceProvider::class,
],
```

## Available Macros

### 1. `whereLike(string $column, string $value, bool $caseSensitive = false)`

Cross-database LIKE search with case sensitivity control.

**Why use this?** Standard `where('column', 'LIKE', '%value%')` doesn't handle case sensitivity consistently across databases. This macro provides a unified API.

**Example:**
```php
// Case-insensitive search (default)
User::whereLike('name', 'john')->get();
// Finds: "John Doe", "johnny", "JOHN", etc.

// Case-sensitive search
User::whereLike('name', 'john', caseSensitive: true)->get();
// Only finds exact case matches

// Standard approach (inconsistent across databases)
User::where('name', 'LIKE', '%john%')->get();
```

**Database Support:**
- MySQL: Uses `LOWER()` function
- PostgreSQL: Uses `ILIKE` operator
- SQLite: Uses `LOWER()` function

---

### 2. `orWhereLike(string $column, string $value, bool $caseSensitive = false)`

OR variant of `whereLike` - adds an OR condition for LIKE search.

**Why use this?** Useful for searching across multiple columns or adding alternative conditions with consistent case handling.

**Example:**
```php
User::where('status', 'active')
    ->orWhereLike('name', 'john')
    ->orWhereLike('email', 'john')
    ->get();

// Standard approach
User::where('status', 'active')
    ->orWhere('name', 'LIKE', '%john%')
    ->orWhere('email', 'LIKE', '%john%')
    ->get();
```

---

### 3. `whereJsonContainsAny(string $column, array $values)`

Check if a JSON column contains ANY of the provided values.

**Why use this?** Laravel's `whereJsonContains()` only checks for a single value. This macro allows checking for multiple values in a single, readable query.

**Example:**
```php
// Find users with any of these tags
User::whereJsonContainsAny('tags', ['admin', 'moderator', 'editor'])->get();

// Find products in any of these categories
Product::whereJsonContainsAny('categories', [1, 2, 3])->get();

// Standard approach (requires multiple whereJsonContains calls)
User::where(function($query) {
    $query->whereJsonContains('tags', 'admin')
          ->orWhereJsonContains('tags', 'moderator')
          ->orWhereJsonContains('tags', 'editor');
})->get();
```

**Database Support:**
- MySQL: Uses `JSON_CONTAINS()`
- PostgreSQL: Uses `@>` operator with `::jsonb`
- SQLite: Uses `json_each()` function

**Edge Cases:**
- Empty array: Returns no results
- Null JSON column: Excluded from results
- Mixed value types: Supported (strings, numbers, booleans)

---

## Testing

Run the test suite:

```bash
composer test
```

Or with Pest:

```bash
./vendor/bin/pest
```

## Code Quality

- ✅ PSR-4 autoloading
- ✅ PSR-12 coding standards
- ✅ Strict types (`declare(strict_types=1)`)
- ✅ Comprehensive PHPDoc blocks
- ✅ Zero dependencies (except Laravel)
- ✅ Cross-database compatibility
- ✅ Comprehensive test coverage

## Contributing

Contributions are welcome! Please ensure:
- All tests pass
- Code follows PSR-12 standards
- New macros include comprehensive tests
- Documentation is updated

## License

MIT

