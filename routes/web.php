<?php

use App\Http\Controllers\Frontend\BrandCatalogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CatalogHomeController;
use App\Http\Controllers\Frontend\CategoryCatalogController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\ProductCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogHomeController::class)->name('home');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
Route::get('/orders/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');

Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])->name('products.show');

Route::get('/categories', [CategoryCatalogController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryCatalogController::class, 'show'])->name('categories.show');

Route::get('/brands', [BrandCatalogController::class, 'index'])->name('brands.index');
Route::get('/brands/{slug}', [BrandCatalogController::class, 'show'])->name('brands.show');
