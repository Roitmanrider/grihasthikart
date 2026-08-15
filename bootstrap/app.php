<?php

use App\Console\Commands\CheckLowStock;
use App\Console\Commands\CleanupCartActivity;
use App\Console\Commands\GenerateMonthlyCartRisk;
use App\Console\Commands\ProcessPendingOrders;
use App\Console\Commands\RecordSchedulerHeartbeat;
use App\Http\Middleware\EnsureCustomerAuthenticated;
use App\Http\Middleware\EnsureStorefrontAccess;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {

            Route::middleware('web')->group(base_path('routes/admin.php'));

            Route::middleware('web')->group(base_path('routes/catalog.php'));

            Route::middleware('web')->group(base_path('routes/customer.php'));

        },
    )
    ->withCommands([
        CleanupCartActivity::class,
        CheckLowStock::class,
        GenerateMonthlyCartRisk::class,
        ProcessPendingOrders::class,
        RecordSchedulerHeartbeat::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->validateCsrfTokens(except: [
            'checkout/razorpay/webhook',
        ]);
        $middleware->alias([
            'customer.auth' => EnsureCustomerAuthenticated::class,
            'storefront.access' => EnsureStorefrontAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return response()->view('errors.admin-404', [], 404);
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
