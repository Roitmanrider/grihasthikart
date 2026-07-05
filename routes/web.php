<?php

use App\Http\Controllers\Frontend\BrandCatalogController;
use App\Http\Controllers\Frontend\CatalogHomeController;
use App\Http\Controllers\Frontend\CategoryCatalogController;
use App\Http\Controllers\Frontend\ProductCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogHomeController::class)->name('home');

Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])->name('products.show');

Route::get('/categories', [CategoryCatalogController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryCatalogController::class, 'show'])->name('categories.show');

Route::get('/brands', [BrandCatalogController::class, 'index'])->name('brands.index');
Route::get('/brands/{slug}', [BrandCatalogController::class, 'show'])->name('brands.show');
