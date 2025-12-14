<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelQueryMacros\QueryMacros\Tests\Models\TestProduct;

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
});

afterEach(function () {
    Schema::dropIfExists('test_products');
});

test('whereJsonContainsAny finds records with any matching value', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics', 'books'])->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product C');
});

test('whereJsonContainsAny works with numeric values', function () {
    $results = TestProduct::whereJsonContainsAny('categories', [1, 5])->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Product A', 'Product C');
});

test('whereJsonContainsAny returns no results when no values match', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['nonexistent'])->get();
    
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
    // So we should only find Product B
    expect($results)->toHaveCount(1);
    expect($results->pluck('name')->toArray())->toContain('Product B');
});

test('whereJsonContainsAny handles single value in array', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['electronics'])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product A');
});

test('whereJsonContainsAny handles null JSON columns', function () {
    $results = TestProduct::whereJsonContainsAny('tags', ['any'])->get();
    
    // Product D has null tags, so it shouldn't match
    expect($results->pluck('name')->toArray())->not->toContain('Product D');
});

test('whereJsonContainsAny works with mixed value types', function () {
    TestProduct::create([
        'name' => 'Product E',
        'tags' => ['mixed', 123, true],
        'categories' => null,
    ]);
    
    $results = TestProduct::whereJsonContainsAny('tags', ['mixed', 123])->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Product E');
});

