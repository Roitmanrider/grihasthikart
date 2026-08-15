<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Customer\Services\CustomerSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CustomerSecurityController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly CustomerSessionService $sessionService
    ) {}

    public function index(Request $request)
    {
        $customer = $this->customer();
        $currentSession = $this->sessionService->validate($request->session());
        $sessions = $customer->sessions()->latest('last_seen_at')->latest()->get();

        return view('frontend.customer.security', compact('customer', 'currentSession', 'sessions'));
    }

    public function destroyOtherSessions(Request $request)
    {
        $customer = $this->customer();
        $count = $this->sessionService->revokeOtherSessions($customer, $request->session());

        return back()->with('success', $count.' other session(s) signed out.');
    }

    private function customer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
