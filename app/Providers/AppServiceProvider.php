<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-admin', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('admin.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
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

        Gate::define('manage-product-images', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('catalog.product-images.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-inventory', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('inventory.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-orders', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('orders.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
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

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
        });

        Gate::define('manage-customers', function (User $user): bool {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo('customers.manage');
            }

            return in_array($user->email, config('grihasthikart.admin_emails', []), true);
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
}
