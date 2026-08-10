<?php

namespace App\Http\Middleware;

use App\Domains\Customer\Services\CustomerAuthService;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EnsureCustomerAuthenticated
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->authService->requireCustomer($request->session());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('customer.login')
                ->withErrors(['customer' => $exception->getMessage()]);
        }

        return $next($request);
    }
}
