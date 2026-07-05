<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Payment\Services\PaymentService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePaymentSettingRequest;

class AdminPaymentSettingController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService,
        private readonly PaymentService $paymentService
    ) {}

    public function edit()
    {
        $settings = $this->settingService->paymentSettings();

        return view('admin.settings.payments', compact('settings'));
    }

    public function update(UpdatePaymentSettingRequest $request)
    {
        $this->paymentService->updateSettings($request->validated());

        return redirect()->route('admin.settings.payments.edit')->with('success', 'Payment settings updated successfully.');
    }
}
