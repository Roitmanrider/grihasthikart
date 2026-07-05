<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\AttributeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkAttributeActionRequest;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AttributeController extends Controller
{
    public function __construct(private readonly AttributeService $attributeService) {}

    public function index(Request $request)
    {
        $attributes = $this->attributeService->paginate(
            $request->only(['search', 'type', 'status', 'is_filterable', 'is_variant_defining', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        $types = Attribute::TYPES;

        return view('admin.attributes.index', compact('attributes', 'types'));
    }

    public function create()
    {
        $types = Attribute::TYPES;

        return view('admin.attributes.create', compact('types'));
    }

    public function store(StoreAttributeRequest $request)
    {
        $this->attributeService->create($request->validated());

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function show(Attribute $attribute)
    {
        return view('admin.attributes.show', compact('attribute'));
    }

    public function edit(Attribute $attribute)
    {
        $types = Attribute::TYPES;

        return view('admin.attributes.edit', compact('attribute', 'types'));
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute)
    {
        $this->attributeService->update($attribute, $request->validated());

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        Gate::authorize('manage-attributes');

        try {
            $this->attributeService->delete($attribute);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['attribute' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    public function restore(int $attribute)
    {
        Gate::authorize('manage-attributes');

        $this->attributeService->restore($attribute);

        return redirect()
            ->route('admin.attributes.index', ['trashed' => 'with'])
            ->with('success', 'Attribute restored successfully.');
    }

    public function bulkAction(BulkAttributeActionRequest $request)
    {
        $data = $request->validated();

        try {
            $count = match ($data['action']) {
                'delete' => $this->attributeService->bulkDelete($data['ids']),
                'activate' => $this->attributeService->bulkUpdateStatus($data['ids'], true),
                'deactivate' => $this->attributeService->bulkUpdateStatus($data['ids'], false),
                'restore' => $this->attributeService->bulkRestore($data['ids']),
            };
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['attribute' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attributes.index', $request->query())
            ->with('success', $count.' attributes processed successfully.');
    }
}
