<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\AttributeService;
use App\Domains\Catalog\Services\AttributeValueService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkAttributeValueActionRequest;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AttributeValueController extends Controller
{
    public function __construct(
        private readonly AttributeValueService $attributeValueService,
        private readonly AttributeService $attributeService
    ) {}

    public function index(Request $request)
    {
        $attributeValues = $this->attributeValueService->paginate(
            $request->only(['search', 'attribute_id', 'status', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        $attributes = $this->attributeService->activeAttributes();

        return view('admin.attribute-values.index', compact('attributeValues', 'attributes'));
    }

    public function create()
    {
        $attributes = $this->attributeService->activeAttributes();

        return view('admin.attribute-values.create', compact('attributes'));
    }

    public function store(StoreAttributeValueRequest $request)
    {
        try {
            $this->attributeValueService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['attribute_value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attribute-values.index')
            ->with('success', 'Attribute value created successfully.');
    }

    public function show(AttributeValue $attributeValue)
    {
        return view('admin.attribute-values.show', compact('attributeValue'));
    }

    public function edit(AttributeValue $attributeValue)
    {
        $attributes = $this->attributeService->activeAttributes();

        return view('admin.attribute-values.edit', compact('attributeValue', 'attributes'));
    }

    public function update(UpdateAttributeValueRequest $request, AttributeValue $attributeValue)
    {
        try {
            $this->attributeValueService->update($attributeValue, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['attribute_value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attribute-values.index')
            ->with('success', 'Attribute value updated successfully.');
    }

    public function destroy(AttributeValue $attributeValue)
    {
        Gate::authorize('manage-attribute-values');

        try {
            $this->attributeValueService->delete($attributeValue);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['attribute_value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attribute-values.index')
            ->with('success', 'Attribute value deleted successfully.');
    }

    public function restore(int $attributeValue)
    {
        Gate::authorize('manage-attribute-values');

        try {
            $this->attributeValueService->restore($attributeValue);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['attribute_value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attribute-values.index', ['trashed' => 'with'])
            ->with('success', 'Attribute value restored successfully.');
    }

    public function bulkAction(BulkAttributeValueActionRequest $request)
    {
        $data = $request->validated();

        try {
            $count = match ($data['action']) {
                'delete' => $this->attributeValueService->bulkDelete($data['ids']),
                'activate' => $this->attributeValueService->bulkUpdateStatus($data['ids'], true),
                'deactivate' => $this->attributeValueService->bulkUpdateStatus($data['ids'], false),
                'restore' => $this->attributeValueService->bulkRestore($data['ids']),
            };
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['attribute_value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.attribute-values.index', $request->query())
            ->with('success', $count.' attribute values processed successfully.');
    }
}
