<?php

declare(strict_types=1);

namespace LaravelQueryMacros\QueryMacros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use LaravelQueryMacros\QueryMacros\Macros\WhereLikeMacro;
use LaravelQueryMacros\QueryMacros\Macros\OrWhereLikeMacro;
use LaravelQueryMacros\QueryMacros\Macros\WhereJsonContainsAnyMacro;

class QueryMacrosServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register whereLike macro
        Builder::macro('whereLike', function (string $column, string $value, bool $caseSensitive = false) {
            return WhereLikeMacro::apply($this, $column, $value, $caseSensitive);
        });

        // Register orWhereLike macro
        Builder::macro('orWhereLike', function (string $column, string $value, bool $caseSensitive = false) {
            return OrWhereLikeMacro::apply($this, $column, $value, $caseSensitive);
        });

        // Register whereJsonContainsAny macro
        Builder::macro('whereJsonContainsAny', function (string $column, array $values) {
            return WhereJsonContainsAnyMacro::apply($this, $column, $values);
        });
    }
}

