<?php

use App\Http\Controllers\Admin\AdminBusinessSettingController;
use App\Http\Controllers\Admin\AdminCashbackController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDeliverySlotController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPaymentSettingController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
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

    /*
    |--------------------------------------------------------------------------
    | Product Images
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-product-images'])->group(function () {
        Route::post('products/{product}/images', [ProductImageController::class, 'store'])
            ->name('products.images.store');
        Route::get('products/{product}/images/{productImage}/edit', [ProductImageController::class, 'edit'])
            ->name('products.images.edit');
        Route::put('products/{product}/images/{productImage}', [ProductImageController::class, 'update'])
            ->name('products.images.update');
        Route::patch('products/{product}/images/{productImage}/primary', [ProductImageController::class, 'setPrimary'])
            ->name('products.images.primary');
        Route::delete('products/{product}/images/{productImage}', [ProductImageController::class, 'destroy'])
            ->name('products.images.destroy');
        Route::patch('products/{product}/images/{productImage}/restore', [ProductImageController::class, 'restore'])
            ->name('products.images.restore');

        Route::post('products/{product}/variants/{productVariant}/images', [ProductImageController::class, 'storeVariant'])
            ->name('products.variants.images.store');
        Route::get('products/{product}/variants/{productVariant}/images/{productImage}/edit', [ProductImageController::class, 'editVariant'])
            ->name('products.variants.images.edit');
        Route::put('products/{product}/variants/{productVariant}/images/{productImage}', [ProductImageController::class, 'updateVariant'])
            ->name('products.variants.images.update');
        Route::patch('products/{product}/variants/{productVariant}/images/{productImage}/primary', [ProductImageController::class, 'setVariantPrimary'])
            ->name('products.variants.images.primary');
        Route::delete('products/{product}/variants/{productVariant}/images/{productImage}', [ProductImageController::class, 'destroyVariant'])
            ->name('products.variants.images.destroy');
        Route::patch('products/{product}/variants/{productVariant}/images/{productImage}/restore', [ProductImageController::class, 'restoreVariant'])
            ->name('products.variants.images.restore');
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-inventory'])->group(function () {
        Route::post('inventories/bulk-action', [InventoryController::class, 'bulkAction'])
            ->name('inventories.bulk-action');

        Route::patch('inventories/{inventory}/restore', [InventoryController::class, 'restore'])
            ->name('inventories.restore');

        Route::get('inventories/{inventory}/adjust', [InventoryController::class, 'adjust'])
            ->name('inventories.adjust');

        Route::post('inventories/{inventory}/adjust', [InventoryController::class, 'storeAdjustment'])
            ->name('inventories.adjust.store');

        Route::resource('inventories', InventoryController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-orders'])->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index'])
            ->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])
            ->name('orders.show');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.update-status');
    });

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-payments'])->group(function () {
        Route::get('payments', [AdminPaymentController::class, 'index'])
            ->name('payments.index');
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])
            ->name('payments.show');
        Route::patch('payments/{payment}/verify', [AdminPaymentController::class, 'verify'])
            ->name('payments.verify');
        Route::patch('payments/{payment}/fail', [AdminPaymentController::class, 'fail'])
            ->name('payments.fail');
    });

    /*
    |--------------------------------------------------------------------------
    | Coupons
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-coupons'])->group(function () {
        Route::post('coupons/bulk-action', [AdminCouponController::class, 'bulkAction'])
            ->name('coupons.bulk-action');
        Route::patch('coupons/{coupon}/restore', [AdminCouponController::class, 'restore'])
            ->name('coupons.restore');
        Route::resource('coupons', AdminCouponController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Cashback
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-cashback'])->group(function () {
        Route::get('cashback', [AdminCashbackController::class, 'index'])->name('cashback.index');
        Route::post('cashback/process', [AdminCashbackController::class, 'process'])->name('cashback.process');
        Route::get('cashback/rules', [AdminCashbackController::class, 'rules'])->name('cashback.rules.index');
        Route::get('cashback/rules/create', [AdminCashbackController::class, 'createRule'])->name('cashback.rules.create');
        Route::post('cashback/rules', [AdminCashbackController::class, 'storeRule'])->name('cashback.rules.store');
        Route::get('cashback/rules/{rule}/edit', [AdminCashbackController::class, 'editRule'])->name('cashback.rules.edit');
        Route::patch('cashback/rules/{rule}', [AdminCashbackController::class, 'updateRule'])->name('cashback.rules.update');
        Route::get('cashback/customers/{customer}', [AdminCashbackController::class, 'customer'])->name('cashback.customers.show');
        Route::get('cashback/redemptions', [AdminCashbackController::class, 'redemptions'])->name('cashback.redemptions.index');
        Route::get('cashback/redemptions/{redemption}', [AdminCashbackController::class, 'redemptionShow'])->name('cashback.redemptions.show');
        Route::patch('cashback/redemptions/{redemption}/approve', [AdminCashbackController::class, 'approve'])->name('cashback.redemptions.approve');
        Route::patch('cashback/redemptions/{redemption}/reject', [AdminCashbackController::class, 'reject'])->name('cashback.redemptions.reject');
        Route::post('cashback/redemptions/{redemption}/generate-coupon', [AdminCashbackController::class, 'generateCoupon'])->name('cashback.redemptions.generate-coupon');
    });

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-customers'])->group(function () {
        Route::patch('customers/{customer}/restore', [AdminCustomerController::class, 'restore'])
            ->name('customers.restore');
        Route::patch('customers/{customer}/status', [AdminCustomerController::class, 'status'])
            ->name('customers.status');
        Route::patch('customers/{customer}/addresses/{address}/approve', [AdminCustomerController::class, 'approveAddress'])
            ->name('customers.addresses.approve');
        Route::resource('customers', AdminCustomerController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Settings and Delivery Slots
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'can:manage-settings'])->group(function () {
        Route::get('settings/checkout', [AdminBusinessSettingController::class, 'edit'])
            ->name('settings.checkout.edit');
        Route::put('settings/checkout', [AdminBusinessSettingController::class, 'update'])
            ->name('settings.checkout.update');
    });

    Route::middleware(['auth', 'can:manage-payment-settings'])->group(function () {
        Route::get('settings/payments', [AdminPaymentSettingController::class, 'edit'])
            ->name('settings.payments.edit');
        Route::patch('settings/payments', [AdminPaymentSettingController::class, 'update'])
            ->name('settings.payments.update');
    });

    Route::middleware(['auth', 'can:manage-delivery-slots'])->group(function () {
        Route::patch('delivery-slots/{deliverySlot}/restore', [AdminDeliverySlotController::class, 'restore'])
            ->name('delivery-slots.restore');
        Route::resource('delivery-slots', AdminDeliverySlotController::class)->except('show');
    });

});
