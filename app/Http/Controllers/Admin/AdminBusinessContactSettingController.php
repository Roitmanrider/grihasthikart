<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessContactSettingRequest;

class AdminBusinessContactSettingController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService
    ) {}

    public function edit()
    {
        return view('admin.settings.business', [
            'settings' => $this->settingService->businessSettings(),
        ]);
    }

    public function update(UpdateBusinessContactSettingRequest $request)
    {
        $this->settingService->updateBusinessSettings($request->validated());

        return redirect()
            ->route('admin.settings.business.edit')
            ->with('success', 'Business contact settings updated successfully.');
    }
}
