<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', function () {
        return view('admin.dashboard.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-categories'])->group(function () {
        Route::post('categories/bulk-action', [CategoryController::class, 'bulkAction'])
            ->name('categories.bulk-action');

        Route::patch('categories/{category}/restore', [CategoryController::class, 'restore'])
            ->name('categories.restore');

        Route::resource('categories', CategoryController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Brands
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-brands'])->group(function () {
        Route::post('brands/bulk-action', [BrandController::class, 'bulkAction'])
            ->name('brands.bulk-action');

        Route::patch('brands/{brand}/restore', [BrandController::class, 'restore'])
            ->name('brands.restore');

        Route::resource('brands', BrandController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-attributes'])->group(function () {
        Route::post('attributes/bulk-action', [AttributeController::class, 'bulkAction'])
            ->name('attributes.bulk-action');

        Route::patch('attributes/{attribute}/restore', [AttributeController::class, 'restore'])
            ->name('attributes.restore');

        Route::resource('attributes', AttributeController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-attribute-values'])->group(function () {
        Route::post('attribute-values/bulk-action', [AttributeValueController::class, 'bulkAction'])
            ->name('attribute-values.bulk-action');

        Route::patch('attribute-values/{attributeValue}/restore', [AttributeValueController::class, 'restore'])
            ->name('attribute-values.restore');

        Route::resource('attribute-values', AttributeValueController::class)
            ->parameters(['attribute-values' => 'attributeValue']);
    });

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-products'])->group(function () {
        Route::post('products/bulk-action', [ProductController::class, 'bulkAction'])
            ->name('products.bulk-action');

        Route::patch('products/{product}/restore', [ProductController::class, 'restore'])
            ->name('products.restore');

        Route::resource('products', ProductController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Product Variants
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-product-variants'])->group(function () {
        Route::post('products/{product}/variants/bulk-action', [ProductVariantController::class, 'bulkAction'])
            ->name('products.variants.bulk-action');

        Route::patch('products/{product}/variants/{productVariant}/restore', [ProductVariantController::class, 'restore'])
            ->name('products.variants.restore');

        Route::resource('products.variants', ProductVariantController::class)
            ->parameters(['variants' => 'productVariant']);
    });

});
