<?php

use App\Http\Controllers\Frontend\BrandCatalogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CatalogHomeController;
use App\Http\Controllers\Frontend\CategoryCatalogController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CustomerAddressController;
use App\Http\Controllers\Frontend\CustomerAuthController;
use App\Http\Controllers\Frontend\CustomerDashboardController;
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

Route::get('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/customer/login', [CustomerAuthController::class, 'requestOtp'])->name('customer.login.request');
Route::get('/customer/verify', [CustomerAuthController::class, 'verifyForm'])->name('customer.otp.verify.form');
Route::post('/customer/verify', [CustomerAuthController::class, 'verify'])->name('customer.otp.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/account', [CustomerDashboardController::class, 'dashboard'])->name('customer.dashboard');
Route::get('/account/orders', [CustomerDashboardController::class, 'orders'])->name('customer.orders.index');
Route::get('/account/orders/{orderNumber}', [CustomerDashboardController::class, 'orderShow'])->name('customer.orders.show');
Route::get('/account/addresses', [CustomerAddressController::class, 'index'])->name('customer.addresses.index');
Route::post('/account/addresses', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
Route::get('/account/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('customer.addresses.edit');
Route::patch('/account/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customer.addresses.update');
Route::delete('/account/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.addresses.destroy');
Route::patch('/account/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('customer.addresses.default');

Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])->name('products.show');

Route::get('/categories', [CategoryCatalogController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryCatalogController::class, 'show'])->name('categories.show');

Route::get('/brands', [BrandCatalogController::class, 'index'])->name('brands.index');
Route::get('/brands/{slug}', [BrandCatalogController::class, 'show'])->name('brands.show');
