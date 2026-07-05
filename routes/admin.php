<?php

use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminOrderController;
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

});
