<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\SiteMediaService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSiteMediaRequest;

class AdminSiteMediaController extends Controller
{
    public function __construct(
        private readonly SiteMediaService $siteMediaService
    ) {}

    public function edit()
    {
        $settings = $this->siteMediaService->settings();

        return view('admin.settings.site-media', compact('settings'));
    }

    public function update(UpdateSiteMediaRequest $request)
    {
        $this->siteMediaService->update($request->validated());

        return redirect()->route('admin.settings.site-media.edit')->with('success', 'Site media updated successfully.');
    }
}
