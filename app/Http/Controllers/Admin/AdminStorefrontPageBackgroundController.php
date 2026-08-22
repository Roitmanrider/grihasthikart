<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\StorefrontPageBackground;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStorefrontPageBackgroundController extends Controller
{
    private const PAGES = ['homepage', 'category', 'search', 'cart', 'checkout', 'customer_account'];

    public function __construct(private readonly MediaService $mediaService) {}

    public function index()
    {
        return view('admin.page-backgrounds.index', [
            'backgrounds' => StorefrontPageBackground::query()->with('stockLocation')->orderBy('page_key')->paginate(30),
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
            'pages' => self::PAGES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stock_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'page_key' => ['required', Rule::in(self::PAGES)],
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'is_enabled' => ['nullable', 'boolean'],
            'opacity' => ['required', 'numeric', 'min:0', 'max:1'],
            'repeat_mode' => ['required', Rule::in(['no-repeat', 'repeat', 'repeat-x', 'repeat-y'])],
            'position' => ['required', 'string', 'max:50'],
            'size_mode' => ['required', Rule::in(['cover', 'contain', 'auto'])],
        ]);

        $background = StorefrontPageBackground::query()->firstOrNew([
            'stock_location_id' => $data['stock_location_id'] ?? null,
            'page_key' => $data['page_key'],
        ]);

        if ($request->hasFile('background')) {
            $background->background_path = $this->mediaService->replace($background->background_path, $request->file('background'), 'site/backgrounds');
        }

        $background->fill([
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'enabled' => (bool) ($data['is_enabled'] ?? false),
            'opacity' => $data['opacity'],
            'repeat_mode' => $data['repeat_mode'],
            'position' => $data['position'],
            'size_mode' => $data['size_mode'],
        ]);

        if (! $background->background_path) {
            return back()->withErrors(['background' => 'A background image is required the first time a page rule is saved.']);
        }

        $background->save();

        return redirect()->route('admin.page-backgrounds.index')->with('success', 'Page background saved.');
    }
}
