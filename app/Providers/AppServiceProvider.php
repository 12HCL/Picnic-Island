<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        // Every paginated list in the project renders Laravel's default pagination view,
        // which is Tailwind markup. This project is Bootstrap and has no Tailwind, so the
        // previous/next arrow SVGs had no size rule and rendered full-screen.
        Paginator::useBootstrapFive();
    }
}
