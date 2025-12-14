<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestProduct extends Model
{
    protected $table = 'test_products';
    protected $fillable = ['name', 'tags', 'categories'];
    protected $casts = [
        'tags' => 'array',
        'categories' => 'array',
    ];
    public $timestamps = false;
}

