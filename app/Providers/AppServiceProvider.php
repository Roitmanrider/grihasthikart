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
    }
}
