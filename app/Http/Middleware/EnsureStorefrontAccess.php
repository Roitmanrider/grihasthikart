<?php

namespace App\Http\Middleware;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EnsureStorefrontAccess
{
    public function __construct(
        private readonly StorefrontAccessService $accessService,
        private readonly CustomerAuthService $authService
    ) {}

    public function handle(Request $request, Closure $next, string $classification = 'catalog')
    {
        if (! $this->accessService->requiresLogin($request, $classification)) {
            return $next($request);
        }

        try {
            $this->authService->requireCustomer($request->session());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->guest(route('customer.login'))
                ->withErrors(['customer' => $exception->getMessage()]);
        }

        return $next($request);
    }
}
