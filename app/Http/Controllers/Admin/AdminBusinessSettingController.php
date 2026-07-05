<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessSettingRequest;

class AdminBusinessSettingController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService
    ) {}

    public function edit()
    {
        $settings = $this->settingService->checkoutSettings();

        return view('admin.settings.checkout', compact('settings'));
    }

    public function update(UpdateBusinessSettingRequest $request)
    {
        $data = $request->validated();
        $data['cod_enabled'] = (bool) ($data['cod_enabled'] ?? false);
        $data['today_delivery_enabled'] = (bool) ($data['today_delivery_enabled'] ?? false);
        $data['custom_delivery_date_enabled'] = (bool) ($data['custom_delivery_date_enabled'] ?? false);

        $this->settingService->updateCheckoutSettings($data);

        return redirect()->route('admin.settings.checkout.edit')->with('success', 'Checkout settings updated successfully.');
    }
}
