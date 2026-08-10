<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStorefrontSeoSettingRequest;

class AdminStorefrontSeoSettingController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService,
        private readonly StorefrontAccessService $storefrontAccessService
    ) {}

    public function edit()
    {
        $settings = $this->settingService->storefrontSettings();
        $seoStatus = $this->storefrontAccessService->seoStatus();
        $modeLabels = $this->storefrontAccessService->modeLabels();

        return view('admin.settings.storefront-seo', compact('settings', 'seoStatus', 'modeLabels'));
    }

    public function update(UpdateStorefrontSeoSettingRequest $request)
    {
        $data = $request->validated();
        $currentSettings = $this->settingService->storefrontSettings();

        $this->settingService->updateStorefrontSettings([
            'access_mode' => $data['access_mode'],
            'homepage_public_in_members_only' => $data['access_mode'] === StorefrontAccessService::MEMBERS_ONLY_STOREFRONT
                ? (bool) ($data['homepage_public_in_members_only'] ?? false)
                : $currentSettings['homepage_public_in_members_only'],
            'allow_guest_checkout' => $data['access_mode'] === StorefrontAccessService::PUBLIC_STOREFRONT
                ? (bool) ($data['allow_guest_checkout'] ?? false)
                : $currentSettings['allow_guest_checkout'],
        ]);

        return redirect()
            ->route('admin.settings.storefront-seo.edit')
            ->with('success', 'Storefront and SEO settings updated successfully.');
    }
}
