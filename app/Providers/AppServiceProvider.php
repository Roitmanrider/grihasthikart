<?php

namespace App\Providers;

use App\Domains\Messaging\Contracts\WhatsAppMessagingServiceInterface;
use App\Domains\Messaging\Services\NullWhatsAppMessagingService;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppMessagingServiceInterface::class, NullWhatsAppMessagingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->configureRateLimiters();

        Gate::before(function (User $user): ?bool {
            if ($user->isSuperAdmin() || in_array($user->email, config('grihasthikart.admin_emails', []), true)) {
                return true;
            }

            return null;
        });

        Gate::define('manage-admin', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('admin.manage');
            }

            return false;
        });

        Gate::define('manage-categories', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.categories.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-brands', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.brands.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-attributes', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.attributes.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-attribute-values', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.attribute-values.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-products', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.products.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-product-variants', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.product-variants.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-product-imports', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.product-imports.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-product-images', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.product-images.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-daily-offers', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.daily-offers.manage');
            }

            return $user->isStoreManager();
        });

        Gate::define('manage-inventory', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('inventory.manage');
            }

            return $user->isStoreManager();
        });

        Gate::define('manage-orders', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('orders.manage');
            }

            return $user->isStoreManager() || $user->isCartFollowUpEmployee();
        });

        Gate::define('manage-payments', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('payments.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-coupons', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('coupons.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-cashback', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('cashback.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-reports', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('reports.manage');
            }

            return $user->isStoreManager();
        });

        Gate::define('manage-customers', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('customers.manage');
            }

            return $user->isStoreManager() || $user->isCartFollowUpEmployee();
        });

        Gate::define('manage-settings', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('settings.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-payment-settings', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('settings.payments.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-delivery-slots', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('delivery-slots.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('customer-login', fn (Request $request) => [
            Limit::perMinute(5)->by((string) $request->input('mobile').'|'.$request->ip()),
        ]);

        RateLimiter::for('customer-otp', fn (Request $request) => [
            Limit::perMinute(8)->by((string) $request->input('mobile').'|'.$request->ip()),
        ]);

        RateLimiter::for('catalog-autocomplete', fn (Request $request) => [
            Limit::perMinute(60)->by($request->ip()),
        ]);

        RateLimiter::for('contact-form', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
        ]);

        RateLimiter::for('coupon-apply', fn (Request $request) => [
            Limit::perMinute(10)->by($request->session()->getId() ?: $request->ip()),
        ]);

        RateLimiter::for('payment-retry', fn (Request $request) => [
            Limit::perMinute(6)->by($request->session()->getId() ?: $request->ip()),
        ]);

        RateLimiter::for('customer-sensitive', fn (Request $request) => [
            Limit::perMinute(12)->by($request->session()->getId() ?: $request->ip()),
        ]);
    }
}
