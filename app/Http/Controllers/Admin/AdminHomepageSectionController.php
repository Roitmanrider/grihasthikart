<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Storefront\Services\HomepageContentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHomepageSectionRequest;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\StockLocation;
use App\Services\MediaService;

class AdminHomepageSectionController extends Controller
{
    public function __construct(
        private readonly HomepageContentService $homepageContentService,
        private readonly MediaService $mediaService
    ) {}

    public function index()
    {
        return view('admin.homepage.sections.index', [
            'sections' => $this->homepageContentService->adminSections(),
        ]);
    }

    public function edit(string $sectionKey)
    {
        $defaults = $this->homepageContentService->defaultsFor($sectionKey);
        $section = HomepageSection::query()
            ->where('section_key', $sectionKey)
            ->with(['selectedCategories', 'selectedProducts'])
            ->first();

        return view('admin.homepage.sections.edit', [
            'section' => $section,
            'defaults' => $defaults,
            'categories' => Category::query()->active()->orderBy('parent_id')->orderBy('display_order')->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('name')->limit(500)->get(),
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateHomepageSectionRequest $request, string $sectionKey)
    {
        $defaults = $this->homepageContentService->defaultsFor($sectionKey);
        $data = $request->validated();

        $section = HomepageSection::query()->where('section_key', $sectionKey)
            ->where('stock_location_id', $data['stock_location_id'] ?? null)
            ->first();

        $payload = [
            'stock_location_id' => $data['stock_location_id'] ?? null,
            'section_key' => $sectionKey,
            'section_type' => $defaults['section_type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'sort_order' => (int) $data['sort_order'],
            'desktop_item_limit' => (int) $data['desktop_item_limit'],
            'mobile_item_limit' => $data['mobile_item_limit'] ?? null,
            'source_mode' => $data['source_mode'] ?? 'automatic',
            'root_category_id' => $data['root_category_id'] ?? null,
            'view_all_enabled' => (bool) ($data['view_all_enabled'] ?? false),
            'view_all_text' => $data['view_all_text'] ?? null,
            'view_all_url' => $data['view_all_url'] ?? null,
        ];

        if ($request->boolean('remove_icon')) {
            $this->mediaService->delete($section?->icon_path);
            $payload['icon_path'] = null;
        } elseif ($request->hasFile('icon')) {
            $payload['icon_path'] = $this->mediaService->replace($section?->icon_path, $request->file('icon'), 'site/section-icons');
        }

        $section = HomepageSection::query()->updateOrCreate(
            ['section_key' => $sectionKey, 'stock_location_id' => $data['stock_location_id'] ?? null],
            $payload
        );

        $this->syncOrdered($section, 'selectedCategories', $data['category_ids'] ?? []);
        $this->syncOrdered($section, 'selectedProducts', $data['product_ids'] ?? []);

        return redirect()->route('admin.homepage.sections.index')->with('success', 'Homepage section updated successfully.');
    }

    private function syncOrdered(HomepageSection $section, string $relation, array $ids): void
    {
        $sync = [];

        foreach (array_values(array_unique(array_filter($ids))) as $index => $id) {
            $sync[(int) $id] = ['sort_order' => $index + 1];
        }

        $section->{$relation}()->sync($sync);
    }
}
