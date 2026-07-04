<?php

namespace App\Providers;

use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domains\Catalog\Repositories\BrandRepository;
use App\Domains\Catalog\Repositories\CategoryRepository;
use Illuminate\Support\ServiceProvider;

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

        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class
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
