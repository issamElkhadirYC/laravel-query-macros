<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelQueryMacros\QueryMacros\Tests\Models\TestUser;

beforeEach(function () {
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    TestUser::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    TestUser::create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    TestUser::create(['name' => 'Bob Johnson', 'email' => 'bob@gmail.com']);
    TestUser::create(['name' => 'Alice Brown', 'email' => 'alice@example.com']);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

test('whereLike finds records with case-insensitive search by default', function () {
    $results = TestUser::whereLike('name', 'john')->get();
    
    // Should find both 'John Doe' and 'Bob Johnson' (contains 'john' in 'Johnson')
    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('John Doe', 'Bob Johnson');
});

test('whereLike finds records with partial match', function () {
    $results = TestUser::whereLike('email', 'gmail')->get();
    
    expect($results)->toHaveCount(1)
        ->and($results->first()->email)->toBe('bob@gmail.com');
});

test('whereLike is case-sensitive when specified', function () {
    // Searching for lowercase 'john' should NOT match 'Bob Johnson' (which has 'Johnson' with capital J)
    // and should NOT match 'John Doe' (which has 'John' with capital J)
    $results = TestUser::whereLike('name', 'john', caseSensitive: true)->get();
    
    // Should find nothing because no name contains lowercase 'john'
    expect($results)->toHaveCount(0);
    
    // Searching for 'John' (capital J) should match both 'John Doe' and 'Bob Johnson' (contains 'John' in 'Johnson')
    $results2 = TestUser::whereLike('name', 'John', caseSensitive: true)->get();
    
    // Should find both because both contain 'John' with capital J
    expect($results2)->toHaveCount(2)
        ->and($results2->pluck('name')->toArray())->toContain('John Doe', 'Bob Johnson');
});

test('whereLike can be chained with other conditions', function () {
    $results = TestUser::where('id', '>', 1)
        ->whereLike('email', 'example')
        ->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('email')->toArray())->toContain('jane@example.com', 'alice@example.com');
});

test('whereLike handles empty string', function () {
    $results = TestUser::whereLike('name', '')->get();
    
    // Empty string with % on both sides should match all records
    expect($results)->toHaveCount(4);
});

test('whereLike handles special characters', function () {
    TestUser::create(['name' => 'Test%User', 'email' => 'test@example.com']);
    
    $results = TestUser::whereLike('name', 'Test%')->get();
    
    expect($results)->toHaveCount(1);
});

