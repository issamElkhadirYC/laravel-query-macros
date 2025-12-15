<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelQueryMacros\QueryMacros\Tests\Models\TestProduct;

/**
 * Test suite for WhereJsonContainsAnyMacro
 * 
 * These tests verify JSON array searching across different database drivers.
 * Note that JSON support varies by database, so behavior may differ slightly.
 */

beforeEach(function () {
    Schema::create('test_products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->json('tags')->nullable();
        $table->json('categories')->nullable();
        $table->timestamps();
    });

    TestProduct::create([
        'name' => 'Product A',
        'tags' => ['electronics', 'gadgets', 'tech'],
        'categories' => [1, 2, 3],
    ]);

    TestProduct::create([
        'name' => 'Product B',
        'tags' => ['clothing', 'fashion'],
        'categories' => [2, 4],
    ]);

    TestProduct::create([
        'name' => 'Product C',
        'tags' => ['books', 'education'],
        'categories' => [5, 6],
    ]);

    TestProduct::create([
        'name' => 'Product D',
        'tags' => null,
        'categories' => null,
    ]);

    TestProduct::create([
        'name' => 'Product E',
        'tags' => ['mixed', 'test'],
        'categories' => [7, 8, 9],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_products');
});

test('whereJsonContainsAny finds records with any matching string value', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics', 'books'])->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product C');
});

test('whereJsonContainsAny works with numeric values', function () {
    $results = TestProduct::whereJsonContainsAny('categories', [1, 5])->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product C');
});

test('whereJsonContainsAny finds records with overlapping numeric values', function () {
    // Both Product A and Product B have category 2
    $results = TestProduct::whereJsonContainsAny('categories', [2])->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product B');
});

test('whereJsonContainsAny returns no results when no values match', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['nonexistent', 'alsonothere'])->get();
    
    expect($results)->toHaveCount(0);
});

test('whereJsonContainsAny returns no results when empty array provided', function () {
    $results = TestProduct::whereJsonContainsAny('tags', [])->get();
    
    expect($results)->toHaveCount(0);
});

test('whereJsonContainsAny can be chained with other conditions', function () {
    $results = TestProduct::where('id', '>', 1)
        ->whereJsonContainsAny('tags', ['fashion', 'tech'])
        ->get();
    
    // Product A (id=1) has 'tech' but is excluded by id > 1
    // Product B (id=2) has 'fashion' and matches
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product B');
});

test('whereJsonContainsAny handles single value in array', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics'])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product A');
});

test('whereJsonContainsAny handles null JSON columns gracefully', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['any', 'value'])->get();
    
    // Product D has null tags, so it shouldn't match
    expect($results->pluck('name')->toArray())->not->toContain('Product D');
});

test('whereJsonContainsAny finds all matching records with multiple criteria', function () {
    // This should find Product A (electronics), B (fashion), and C (education)
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics', 'fashion', 'education'])->get();
    
    expect($results)->toHaveCount(3);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product B', 'Product C');
});

test('whereJsonContainsAny can be chained multiple times', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics'])
        ->whereJsonContainsAny('categories', [1, 2, 3])
        ->get();
    
    // Only Product A has 'electronics' AND one of categories [1,2,3]
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product A');
});

test('whereJsonContainsAny works with large numbers', function () {
    TestProduct::create([
        'name' => 'Product F',
        'tags' => ['test'],
        'categories' => [999999, 1000000],
    ]);
    
    $results = TestProduct::whereJsonContainsAny('categories', [999999])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product F');
});

test('whereJsonContainsAny handles duplicate values in search array', function () {
    // Duplicate values shouldn't cause issues
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics', 'electronics', 'tech'])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product A');
});

test('whereJsonContainsAny verifies actual query execution', function () {
    $query = TestProduct::whereJsonContainsAny('tags', ['electronics', 'books']);
    $sql = $query->toSql();
    $bindings = $query->getBindings();
    
    expect($sql)->toBeString();
    expect($bindings)->toBeArray();
    expect($bindings)->toHaveCount(2);
    
    $results = $query->get();
    expect($results)->toHaveCount(2);
});

test('whereJsonContainsAny works with special characters in strings', function () {
    TestProduct::create([
        'name' => 'Product G',
        'tags' => ['special-chars', 'test@example', 'hello_world'],
        'categories' => null,
    ]);
    
    $results = TestProduct::whereJsonContainsAny('tags', ['special-chars', 'test@example'])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product G');
});

test('whereJsonContainsAny handles unicode characters', function () {
    TestProduct::create([
        'name' => 'Product H',
        'tags' => ['café', '日本語', 'émoji'],
        'categories' => null,
    ]);
    
    $results = TestProduct::whereJsonContainsAny('tags', ['café', '日本語'])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product H');
});