<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
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

});
