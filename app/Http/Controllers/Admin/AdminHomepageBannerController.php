<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHomepageBannerRequest;
use App\Http\Requests\UpdateHomepageBannerRequest;
use App\Models\HomepageBanner;
use App\Services\MediaService;

class AdminHomepageBannerController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index()
    {
        return view('admin.homepage.banners.index', [
            'banners' => HomepageBanner::query()->orderBy('sort_order')->orderBy('id')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.homepage.banners.create', ['banner' => new HomepageBanner]);
    }

    public function store(StoreHomepageBannerRequest $request)
    {
        $data = $this->payload($request->validated());
        $data['desktop_image_path'] = $this->mediaService->store($request->file('desktop_image'), 'site/banners');

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image_path'] = $this->mediaService->store($request->file('mobile_image'), 'site/banners');
        }

        HomepageBanner::query()->create($data);

        return redirect()->route('admin.homepage.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(HomepageBanner $banner)
    {
        return view('admin.homepage.banners.edit', compact('banner'));
    }

    public function update(UpdateHomepageBannerRequest $request, HomepageBanner $banner)
    {
        $data = $this->payload($request->validated());

        if ($request->hasFile('desktop_image')) {
            $data['desktop_image_path'] = $this->mediaService->replace($banner->desktop_image_path, $request->file('desktop_image'), 'site/banners');
        }

        if ($request->boolean('remove_mobile_image')) {
            $this->mediaService->delete($banner->mobile_image_path);
            $data['mobile_image_path'] = null;
        } elseif ($request->hasFile('mobile_image')) {
            $data['mobile_image_path'] = $this->mediaService->replace($banner->mobile_image_path, $request->file('mobile_image'), 'site/banners');
        }

        $banner->update($data);

        return redirect()->route('admin.homepage.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(HomepageBanner $banner)
    {
        $this->mediaService->delete($banner->desktop_image_path);
        $this->mediaService->delete($banner->mobile_image_path);
        $banner->delete();

        return redirect()->route('admin.homepage.banners.index')->with('success', 'Banner deleted successfully.');
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'cta_text' => $data['cta_text'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'open_in_new_tab' => (bool) ($data['open_in_new_tab'] ?? false),
            'alt_text' => $data['alt_text'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'sort_order' => (int) $data['sort_order'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];
    }
}
