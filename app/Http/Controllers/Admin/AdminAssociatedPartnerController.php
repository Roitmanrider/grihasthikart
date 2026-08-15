<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssociatedPartnerRequest;
use App\Http\Requests\UpdateAssociatedPartnerRequest;
use App\Models\AssociatedPartner;
use App\Services\MediaService;

class AdminAssociatedPartnerController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index()
    {
        return view('admin.homepage.partners.index', [
            'partners' => AssociatedPartner::query()->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.homepage.partners.create', ['partner' => new AssociatedPartner]);
    }

    public function store(StoreAssociatedPartnerRequest $request)
    {
        $data = $this->payload($request->validated());

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->mediaService->store($request->file('image'), 'site/partners');
        }

        AssociatedPartner::query()->create($data);

        return redirect()->route('admin.homepage.partners.index')->with('success', 'Associated partner created successfully.');
    }

    public function edit(AssociatedPartner $partner)
    {
        return view('admin.homepage.partners.edit', compact('partner'));
    }

    public function update(UpdateAssociatedPartnerRequest $request, AssociatedPartner $partner)
    {
        $data = $this->payload($request->validated());

        if ($request->boolean('remove_image')) {
            $this->mediaService->delete($partner->image_path);
            $data['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            $data['image_path'] = $this->mediaService->replace($partner->image_path, $request->file('image'), 'site/partners');
        }

        $partner->update($data);

        return redirect()->route('admin.homepage.partners.index')->with('success', 'Associated partner updated successfully.');
    }

    public function destroy(AssociatedPartner $partner)
    {
        $this->mediaService->delete($partner->image_path);
        $partner->delete();

        return redirect()->route('admin.homepage.partners.index')->with('success', 'Associated partner deleted successfully.');
    }

    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'external_url' => $data['external_url'] ?? null,
            'promo_text' => $data['promo_text'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'sort_order' => (int) $data['sort_order'],
        ];
    }
}
