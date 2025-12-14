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

test('orWhereLike adds OR condition for LIKE search', function () {
    $results = TestUser::where('id', 1)
        ->orWhereLike('name', 'jane')
        ->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('John Doe', 'Jane Smith');
});

test('orWhereLike can be chained multiple times', function () {
    $results = TestUser::where('id', 1)
        ->orWhereLike('name', 'bob')
        ->orWhereLike('email', 'alice')
        ->get();
    
    expect($results)->toHaveCount(3);
    expect($results->pluck('name')->toArray())->toContain('John Doe', 'Bob Johnson', 'Alice Brown');
});

test('orWhereLike is case-insensitive by default', function () {
    $results = TestUser::where('id', 1)
        ->orWhereLike('name', 'JANE')
        ->get();
    
    expect($results)->toHaveCount(2);
});

test('orWhereLike is case-sensitive when specified', function () {
    $results = TestUser::where('id', 1)
        ->orWhereLike('name', 'JANE', caseSensitive: true)
        ->get();
    
    // Should only find John Doe (id=1) because JANE doesn't match Jane (case-sensitive)
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('John Doe');
    
    // But 'Jane' (with capital J) should match
    $results2 = TestUser::where('id', 1)
        ->orWhereLike('name', 'Jane', caseSensitive: true)
        ->get();
    
    expect($results2)->toHaveCount(2);
    expect($results2->pluck('name')->toArray())->toContain('John Doe', 'Jane Smith');
});

test('orWhereLike works with initial whereLike', function () {
    $results = TestUser::whereLike('name', 'john')
        ->orWhereLike('name', 'jane')
        ->get();
    
    // Should find: John Doe, Bob Johnson (both contain 'john'), and Jane Smith (contains 'jane')
    expect($results)->toHaveCount(3);
    expect($results->pluck('name')->toArray())->toContain('John Doe', 'Bob Johnson', 'Jane Smith');
});

