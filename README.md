# Laravel Query Macros

[![Tests](https://github.com/issamElkhadirYC/laravel-query-macros/actions/workflows/tests.yml/badge.svg)](https://github.com/issamElkhadirYC/laravel-query-macros/actions)
[![Latest Version](https://img.shields.io/packagist/v/issam-elkhadir/laravel-query-macros.svg)](https://packagist.org/packages/issam-elkhadir/laravel-query-macros)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D10.0-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A collection of useful, production-ready Eloquent query macros that solve common pain points in Laravel applications.

## Features

- 🔍 **Cross-database LIKE search** with case sensitivity control
- 📦 **JSON array searching** - check if JSON contains ANY of multiple values
- 🚀 **Zero dependencies** (except Laravel)
- ✅ **Production-tested** with comprehensive test suite
- 🌐 **Multi-database support** - MySQL, PostgreSQL, SQLite, SQL Server
- 🎯 **Type-safe** with strict PHP 8.2+ typing

## Requirements

- PHP 8.2+
- Laravel 10+ or 11+

## Installation
```bash
composer require issam-elkhadir/laravel-query-macros
```

The service provider will be auto-discovered. If you're using Laravel < 5.5, manually register it in `config/app.php`:
```php
'providers' => [
    // ...
    LaravelQueryMacros\QueryMacros\QueryMacrosServiceProvider::class,
],
```

## Available Macros

### 1. `whereLike()` - Cross-Database LIKE Search

Cross-database LIKE search with case sensitivity control and wildcard escaping.

**Signature:**
```php
whereLike(string $column, string $value, bool $caseSensitive = false, bool $escapeWildcards = false)
```

**Why use this?** Standard `where('column', 'LIKE', '%value%')` doesn't handle case sensitivity consistently across databases. This macro provides a unified API with performance optimizations per database.

**Examples:**
```php
// Case-insensitive search (default)
User::whereLike('name', 'john')->get();
// Finds: "John Doe", "johnny", "JOHN", etc.

// Case-sensitive search
User::whereLike('name', 'john', caseSensitive: true)->get();
// Only finds exact case matches

// Escape wildcards for literal % or _ search
Product::whereLike('name', '100%', escapeWildcards: true)->get();
// Finds products with literal "100%" in name

// Chain with other conditions
User::where('status', 'active')
    ->whereLike('name', 'john')
    ->get();

// Multiple whereLike conditions
User::whereLike('name', 'john')
    ->whereLike('email', 'example.com')
    ->get();
```

**Database Implementation:**
- **MySQL/MariaDB**: Uses `LOWER()` function for case-insensitive, `LIKE BINARY` for case-sensitive
- **PostgreSQL**: Uses `ILIKE` operator for case-insensitive, `LIKE` for case-sensitive
- **SQLite**: Uses `LOWER()` function for case-insensitive, `COLLATE BINARY` for case-sensitive
- **SQL Server**: Uses `LOWER()` function

**Performance Note:** Case-insensitive searches using `LOWER()` cannot utilize indexes. For large datasets with frequent searches, consider:
- Adding a full-text index
- Creating a generated/computed column with lowercase values
- Using database-specific full-text search features

---

### 2. `orWhereLike()` - OR Variant of whereLike

OR variant of `whereLike` - adds an OR condition for LIKE search.

**Signature:**
```php
orWhereLike(string $column, string $value, bool $caseSensitive = false, bool $escapeWildcards = false)
```

**Why use this?** Useful for searching across multiple columns or adding alternative conditions with consistent case handling.

**Examples:**
```php
// Search in multiple columns with OR
User::where('status', 'active')
    ->orWhereLike('name', 'john')
    ->orWhereLike('email', 'john')
    ->get();

// Combine whereLike and orWhereLike
User::whereLike('name', 'john')
    ->orWhereLike('name', 'jane')
    ->get();

// With initial WHERE condition
User::where('id', '>', 100)
    ->orWhereLike('email', 'gmail.com')
    ->get();
```

---

### 3. `whereJsonContainsAny()` - JSON Array Search

Check if a JSON column contains ANY of the provided values.

**Signature:**
```php
whereJsonContainsAny(string $column, array $values)
```

**Why use this?** Laravel's `whereJsonContains()` only checks for a single value. This macro allows checking for multiple values in a single, efficient query.

**Examples:**
```php
// Find users with any of these roles
User::whereJsonContainsAny('roles', ['admin', 'moderator', 'editor'])->get();

// Find products in any of these categories (numeric IDs)
Product::whereJsonContainsAny('category_ids', [1, 2, 3])->get();

// Search for tags
Post::whereJsonContainsAny('tags', ['laravel', 'php', 'vue'])->get();

// Chain with other conditions
Product::where('status', 'active')
    ->whereJsonContainsAny('tags', ['featured', 'popular'])
    ->get();

// Multiple JSON conditions
Product::whereJsonContainsAny('tags', ['electronics'])
    ->whereJsonContainsAny('categories', [1, 2, 3])
    ->get();
```

**Supported Value Types:**
- ✅ Strings: `['admin', 'moderator']`
- ✅ Numbers: `[1, 2, 3]`
- ✅ Mixed types: `['admin', 123, true]`

**Database Implementation:**
- **MySQL/MariaDB**: Uses `JSON_CONTAINS()` function (MySQL 5.7+)
- **PostgreSQL**: Uses `?|` operator with `::jsonb` (PostgreSQL 9.4+)
- **SQLite**: Uses `json_each()` function (SQLite 3.9+)
- **SQL Server**: Uses `OPENJSON()` function (SQL Server 2016+)

**Edge Cases:**
- Empty array (`[]`): Returns no results (returns `WHERE 1 = 0`)
- Null JSON column: Excluded from results
- Duplicate values in search array: Handled correctly (no duplicates in results)
- Special characters: Properly encoded with `JSON_UNESCAPED_UNICODE`
- Unicode strings: Fully supported (emoji, Chinese, Arabic, etc.)

**Performance Note:** JSON operations can be slower than regular column searches. For frequently queried JSON columns, consider:
- Adding JSON indexes (MySQL 5.7+, PostgreSQL supports GIN indexes on JSONB)
- Normalizing data into separate tables if performance is critical
- Using generated/virtual columns for frequently searched JSON keys

**Example JSON Index (MySQL):**
```php
// In your migration
Schema::table('users', function (Blueprint $table) {
    $table->index('roles', 'users_roles_index', 'json');
});
```

---

## Database Support

| Database | whereLike | orWhereLike | whereJsonContainsAny | Notes |
|----------|-----------|-------------|----------------------|-------|
| **MySQL 5.7+** | ✅ | ✅ | ✅ | Full support |
| **MariaDB 10.2+** | ✅ | ✅ | ✅ | Full support |
| **PostgreSQL 9.4+** | ✅ | ✅ | ✅ | Full support, uses JSONB |
| **SQLite 3.9+** | ✅ | ✅ | ✅ | See limitations below |
| **SQL Server 2016+** | ✅ | ✅ | ✅ | Full support |

### SQLite Limitations

**escapeWildcards Parameter:**
SQLite's `LIKE` operator does not support the standard SQL `ESCAPE` clause. This means:

- ⚠️ `escapeWildcards: true` has **limited effect** in SQLite
- ✅ Works correctly on **MySQL, PostgreSQL, and SQL Server**
- 🔧 **Workaround for SQLite**: Use exact matching with `where('column', '=', 'value')` if you need literal wildcard characters

**Example of the limitation:**
```php
// On MySQL/PostgreSQL - works as expected
Product::whereLike('name', '100%', escapeWildcards: true)->get();
// Returns only products with literal "100%" in name

// On SQLite - may match more broadly
Product::whereLike('name', '100%', escapeWildcards: true)->get();
// May also match "100x", "1000", etc. due to SQLite limitations
```

**Recommendation:** For production applications using MySQL, PostgreSQL, or SQL Server, the `escapeWildcards` feature works correctly and can be used confidently.

---

## Performance Considerations

### Indexing Strategies

**For LIKE Searches:**
```php
// Case-sensitive searches can use regular indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
});

// Case-insensitive searches benefit from expression indexes (PostgreSQL)
DB::statement('CREATE INDEX users_email_lower_idx ON users (LOWER(email))');

// Or use full-text indexes for better performance
Schema::table('posts', function (Blueprint $table) {
    $table->fullText('content');
});
```

**For JSON Searches:**
```php
// MySQL 8.0+ multi-valued index on JSON array
Schema::table('products', function (Blueprint $table) {
    DB::statement('ALTER TABLE products ADD INDEX tags_idx ((CAST(tags AS CHAR(255) ARRAY)))');
});

// PostgreSQL GIN index on JSONB
DB::statement('CREATE INDEX products_tags_gin_idx ON products USING GIN (tags)');
```

### When NOT to Use These Macros

- ❌ **Full-text search needs**: Use Laravel Scout or database full-text search
- ❌ **Very large datasets (millions of rows)**: Use specialized search engines (Elasticsearch, Meilisearch)
- ❌ **Complex text matching**: Use regular expressions or full-text search features
- ❌ **When you need ranking/relevance scores**: Use full-text search

### When to Use These Macros

- ✅ **Simple partial matching**: Finding names, emails, descriptions
- ✅ **Cross-database compatibility**: Need same behavior across databases
- ✅ **Small to medium datasets**: < 100k rows with proper indexes
- ✅ **JSON array membership**: Checking if value exists in JSON array
- ✅ **Case-sensitive control**: Need explicit case handling

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

Run tests with coverage:
```bash
./vendor/bin/pest --coverage
```

### Test Coverage

- ✅ All macros tested across multiple scenarios
- ✅ Edge cases (empty strings, null values, special characters, unicode)
- ✅ Cross-database compatibility (tests run on SQLite)
- ✅ Query execution verification (not just syntax checking)
- ✅ Case sensitivity testing
- ✅ Chainability testing

---

## Code Quality

- ✅ **PSR-4** autoloading
- ✅ **PSR-12** coding standards
- ✅ **Strict types** (`declare(strict_types=1)`)
- ✅ **Comprehensive PHPDoc** blocks with examples
- ✅ **Zero dependencies** (except Laravel)
- ✅ **Cross-database compatibility** tested
- ✅ **Type-safe** with PHP 8.2+ features
- ✅ **SQL injection prevention** via parameterized queries

---

## Real-World Examples

### User Search with Multiple Criteria
```php
// Search users by name or email
User::where('status', 'active')
    ->where(function($query) use ($searchTerm) {
        $query->whereLike('name', $searchTerm)
              ->orWhereLike('email', $searchTerm);
    })
    ->paginate(20);
```

### Product Filtering by Tags
```php
// Find products with any of the selected tags
Product::where('in_stock', true)
    ->whereJsonContainsAny('tags', $selectedTags)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Case-Sensitive Email Search
```php
// Find exact email match (case-sensitive)
User::whereLike('email', $email, caseSensitive: true)->first();
```

### Multi-Role Access Control
```php
// Find users with any admin-level role
User::whereJsonContainsAny('roles', ['super-admin', 'admin', 'moderator'])
    ->get();
```

---

## Contributing

Contributions are welcome! Please ensure:

1. **All tests pass**: `composer test`
2. **Code follows PSR-12** standards
3. **New macros include**:
   - Comprehensive tests (including edge cases)
   - PHPDoc blocks with examples
   - Cross-database support where applicable
   - Performance notes
4. **Documentation is updated** in README.md

### Development Setup
```bash
# Clone the repository
git clone https://github.com/issamElkhadirYC/laravel-query-macros.git

# Install dependencies
composer install

# Run tests
composer test
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

## Security

If you discover any security-related issues, please email issamelkhader55@gmail.com instead of using the issue tracker.

---

## Credits

- **[Issam Elkhadir](https://github.com/issamElkhadirYC)**
- [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

---

## Support

- 📖 [Documentation](https://github.com/issamElkhadirYC/laravel-query-macros/wiki)
- 🐛 [Issue Tracker](https://github.com/issamElkhadirYC/laravel-query-macros/issues)
- 💬 [Discussions](https://github.com/issamElkhadirYC/laravel-query-macros/discussions)

---
