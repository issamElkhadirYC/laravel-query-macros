<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'test_users';
    protected $fillable = ['name', 'email'];
    public $timestamps = false;
}

