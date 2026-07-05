<?php

namespace App\Providers;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Domains\Catalog\Contracts\AttributeValueRepositoryInterface;
use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Domains\Catalog\Contracts\ProductImageRepositoryInterface;
use App\Domains\Catalog\Contracts\ProductRepositoryInterface;
use App\Domains\Catalog\Contracts\ProductVariantRepositoryInterface;
use App\Domains\Catalog\Repositories\AttributeRepository;
use App\Domains\Catalog\Repositories\AttributeValueRepository;
use App\Domains\Catalog\Repositories\BrandRepository;
use App\Domains\Catalog\Repositories\CategoryRepository;
use App\Domains\Catalog\Repositories\ProductImageRepository;
use App\Domains\Catalog\Repositories\ProductRepository;
use App\Domains\Catalog\Repositories\ProductVariantRepository;
use App\Domains\Inventory\Contracts\InventoryRepositoryInterface;
use App\Domains\Inventory\Repositories\InventoryRepository;
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

        $this->app->bind(
            AttributeRepositoryInterface::class,
            AttributeRepository::class
        );

        $this->app->bind(
            AttributeValueRepositoryInterface::class,
            AttributeValueRepository::class
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            ProductImageRepositoryInterface::class,
            ProductImageRepository::class
        );

        $this->app->bind(
            ProductVariantRepositoryInterface::class,
            ProductVariantRepository::class
        );

        $this->app->bind(
            InventoryRepositoryInterface::class,
            InventoryRepository::class
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
