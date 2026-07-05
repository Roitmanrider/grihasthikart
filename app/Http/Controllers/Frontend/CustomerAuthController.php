<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLoginRequest;
use App\Http\Requests\VerifyCustomerOtpRequest;
use InvalidArgumentException;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService
    ) {}

    public function login()
    {
        return view('frontend.customer.login');
    }

    public function requestOtp(CustomerLoginRequest $request)
    {
        try {
            $otp = $this->authService->requestOtp($request->validated('mobile'));
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['mobile' => $exception->getMessage()]);
        }

        return redirect()
            ->route('customer.otp.verify.form', ['mobile' => $request->validated('mobile')])
            ->with('success', 'OTP for local login: '.$otp);
    }

    public function verifyForm()
    {
        return view('frontend.customer.verify');
    }

    public function verify(VerifyCustomerOtpRequest $request)
    {
        $data = $request->validated();

        try {
            $this->authService->verifyOtp($request->session(), $data['mobile'], $data['otp']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['otp' => $exception->getMessage()]);
        }

        return redirect()->intended(route('customer.dashboard'))->with('success', 'Logged in successfully.');
    }

    public function logout()
    {
        $this->authService->logout(request()->session());

        return redirect()->route('home')->with('success', 'Logged out successfully.');
    }
}
