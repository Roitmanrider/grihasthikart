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
    }
}
