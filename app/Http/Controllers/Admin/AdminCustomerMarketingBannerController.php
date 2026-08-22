<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerMarketingBanner;
use App\Models\StockLocation;
use App\Services\MediaService;
use Illuminate\Http\Request;

class AdminCustomerMarketingBannerController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index()
    {
        return view('admin.marketing-banners.index', [
            'banners' => CustomerMarketingBanner::query()
                ->withCount('stores')
                ->orderByDesc('priority')
                ->orderBy('display_order')
                ->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.marketing-banners.form', $this->formData(new CustomerMarketingBanner));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_path'] = $this->mediaService->store($request->file('image'), 'site/customer-banners');

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image_path'] = $this->mediaService->store($request->file('mobile_image'), 'site/customer-banners');
        }

        $banner = CustomerMarketingBanner::query()->create($this->payload($data, $request));
        $banner->stores()->sync($data['store_ids'] ?? []);

        return redirect()->route('admin.marketing-banners.index')->with('success', 'Marketing banner created.');
    }

    public function edit(CustomerMarketingBanner $marketingBanner)
    {
        $marketingBanner->load('stores');

        return view('admin.marketing-banners.form', $this->formData($marketingBanner));
    }

    public function update(Request $request, CustomerMarketingBanner $marketingBanner)
    {
        $data = $this->validated($request, false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->mediaService->replace($marketingBanner->image_path, $request->file('image'), 'site/customer-banners');
        }

        if ($request->boolean('remove_mobile_image')) {
            $this->mediaService->delete($marketingBanner->mobile_image_path);
            $data['mobile_image_path'] = null;
        } elseif ($request->hasFile('mobile_image')) {
            $data['mobile_image_path'] = $this->mediaService->replace($marketingBanner->mobile_image_path, $request->file('mobile_image'), 'site/customer-banners');
        }

        $marketingBanner->update($this->payload($data, $request, $marketingBanner));
        $marketingBanner->stores()->sync($data['store_ids'] ?? []);

        return redirect()->route('admin.marketing-banners.index')->with('success', 'Marketing banner updated.');
    }

    public function destroy(CustomerMarketingBanner $marketingBanner)
    {
        $marketingBanner->update([
            'enabled' => false,
            'inactive_since' => $marketingBanner->inactive_since ?? now('Asia/Kolkata'),
        ]);

        return redirect()->route('admin.marketing-banners.index')->with('success', 'Marketing banner deactivated.');
    }

    private function formData(CustomerMarketingBanner $banner): array
    {
        return [
            'banner' => $banner,
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, bool $imageRequired = true): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_mobile_image' => ['nullable', 'boolean'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stock_locations,id'],
            'display_order' => ['required', 'integer', 'min:0', 'max:255'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'enabled' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $data, Request $request, ?CustomerMarketingBanner $banner = null): array
    {
        $enabled = (bool) ($data['enabled'] ?? false);

        $payload = [
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'cta_text' => $data['cta_text'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'display_order' => (int) $data['display_order'],
            'priority' => (int) $data['priority'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'enabled' => $enabled,
            'inactive_since' => $enabled ? null : ($banner?->inactive_since ?? now('Asia/Kolkata')),
            'created_by' => $banner?->created_by ?? $request->user()?->id,
        ];

        if (array_key_exists('image_path', $data)) {
            $payload['image_path'] = $data['image_path'];
        }

        if (array_key_exists('mobile_image_path', $data)) {
            $payload['mobile_image_path'] = $data['mobile_image_path'];
        }

        return $payload;
    }

    private function safeUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || str_starts_with((string) $value, '/')) {
                return;
            }

            if (! in_array(parse_url((string) $value, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $fail('The '.$attribute.' must be a relative URL or an http/https URL.');
            }
        };
    }
}
