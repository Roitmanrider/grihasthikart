<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domains\Catalog\Repositories\CategoryRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
