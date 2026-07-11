<?php

use App\Http\Controllers\Frontend\BrandCatalogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CatalogHomeController;
use App\Http\Controllers\Frontend\CategoryCatalogController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\ContentPageController;
use App\Http\Controllers\Frontend\CouponController;
use App\Http\Controllers\Frontend\CustomerAddressController;
use App\Http\Controllers\Frontend\CustomerAuthController;
use App\Http\Controllers\Frontend\CustomerCashbackController;
use App\Http\Controllers\Frontend\CustomerDashboardController;
use App\Http\Controllers\Frontend\CustomerOrderDocumentController;
use App\Http\Controllers\Frontend\DailyOfferCatalogController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\ProductCatalogController;
use App\Http\Controllers\Frontend\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::get('/', CatalogHomeController::class)->name('home');
Route::get('/daily-offers', [DailyOfferCatalogController::class, 'index'])->name('daily-offers.index');

Route::get('/about-us', [ContentPageController::class, 'page'])
    ->defaults('page', 'about-us')
    ->name('pages.about');
Route::get('/contact-us', [ContentPageController::class, 'contact'])->name('pages.contact');
Route::post('/contact-us', [ContentPageController::class, 'storeContact'])->name('contact-messages.store');
Route::get('/privacy-policy', [ContentPageController::class, 'page'])
    ->defaults('page', 'privacy-policy')
    ->name('pages.privacy');
Route::get('/terms-and-conditions', [ContentPageController::class, 'page'])
    ->defaults('page', 'terms-and-conditions')
    ->name('pages.terms');
Route::get('/shipping-and-cancellation', [ContentPageController::class, 'page'])
    ->defaults('page', 'shipping-and-cancellation')
    ->name('pages.shipping');
Route::get('/return-and-refund', [ContentPageController::class, 'page'])
    ->defaults('page', 'return-and-refund')
    ->name('pages.returns');
Route::get('/disclaimer', [ContentPageController::class, 'page'])
    ->defaults('page', 'disclaimer')
    ->name('pages.disclaimer');
Route::get('/faqs', [ContentPageController::class, 'faqs'])->name('pages.faqs');
Route::get('/customer-support', [ContentPageController::class, 'page'])
    ->defaults('page', 'customer-support')
    ->name('pages.support');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/coupon/apply', [CouponController::class, 'apply'])->name('cart.coupon.apply');
Route::delete('/cart/coupon/remove', [CouponController::class, 'remove'])->name('cart.coupon.remove');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('wishlist.items.store');
Route::post('/wishlist/items/{wishlistItem}/move-to-cart', [WishlistController::class, 'moveToCart'])
    ->name('wishlist.items.move-to-cart')
    ->missing(fn () => back()->withErrors(['wishlist' => 'Wishlist item is no longer available.']));
Route::delete('/wishlist/items/{wishlistItem}', [WishlistController::class, 'destroy'])->name('wishlist.items.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
Route::post('/checkout/razorpay/order', [CheckoutController::class, 'createRazorpayOrder'])->name('checkout.razorpay.order');
Route::post('/checkout/razorpay/verify', [CheckoutController::class, 'verifyRazorpayPayment'])->name('checkout.razorpay.verify');
Route::post('/checkout/razorpay/failure', [CheckoutController::class, 'failRazorpayPayment'])->name('checkout.razorpay.failure');
Route::get('/orders/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/orders/{orderNumber}/payment-proof', [PaymentController::class, 'uploadProof'])->name('orders.payment-proof.store');

Route::get('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/customer/login', [CustomerAuthController::class, 'requestOtp'])->name('customer.login.request');
Route::get('/customer/verify', [CustomerAuthController::class, 'verifyForm'])->name('customer.otp.verify.form');
Route::post('/customer/verify', [CustomerAuthController::class, 'verify'])->name('customer.otp.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/account', [CustomerDashboardController::class, 'dashboard'])->name('customer.dashboard');
Route::get('/account/orders', [CustomerDashboardController::class, 'orders'])->name('customer.orders.index');
Route::get('/account/orders/{order}/invoice', [CustomerOrderDocumentController::class, 'invoice'])->name('customer.orders.invoice');
Route::patch('/account/orders/{orderNumber}/cancel', [CustomerDashboardController::class, 'cancelOrder'])->name('customer.orders.cancel');
Route::get('/account/orders/{orderNumber}', [CustomerDashboardController::class, 'orderShow'])->name('customer.orders.show');
Route::get('/account/cashback', [CustomerCashbackController::class, 'index'])->name('customer.cashback.index');
Route::post('/account/cashback/redeem', [CustomerCashbackController::class, 'redeem'])->name('customer.cashback.redeem');
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
