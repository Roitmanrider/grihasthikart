<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Delivery\Services\DeliverySlotService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliverySlotRequest;
use App\Http\Requests\UpdateDeliverySlotRequest;
use App\Models\DeliverySlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AdminDeliverySlotController extends Controller
{
    public function __construct(
        private readonly DeliverySlotService $deliverySlotService
    ) {}

    public function index(Request $request)
    {
        $slots = $this->deliverySlotService->paginate($request->only(['search', 'status', 'trashed']));

        return view('admin.delivery-slots.index', compact('slots'));
    }

    public function create()
    {
        return view('admin.delivery-slots.create');
    }

    public function store(StoreDeliverySlotRequest $request)
    {
        try {
            $this->deliverySlotService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['slot' => $exception->getMessage()]);
        }

        return redirect()->route('admin.delivery-slots.index')->with('success', 'Delivery slot created successfully.');
    }

    public function edit(DeliverySlot $deliverySlot)
    {
        return view('admin.delivery-slots.edit', compact('deliverySlot'));
    }

    public function update(DeliverySlot $deliverySlot, UpdateDeliverySlotRequest $request)
    {
        try {
            $this->deliverySlotService->update($deliverySlot, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['slot' => $exception->getMessage()]);
        }

        return redirect()->route('admin.delivery-slots.index')->with('success', 'Delivery slot updated successfully.');
    }

    public function destroy(DeliverySlot $deliverySlot)
    {
        Gate::authorize('manage-delivery-slots');
        $this->deliverySlotService->delete($deliverySlot);

        return redirect()->route('admin.delivery-slots.index')->with('success', 'Delivery slot deleted successfully.');
    }

    public function restore(int $deliverySlot)
    {
        Gate::authorize('manage-delivery-slots');
        $this->deliverySlotService->restore($deliverySlot);

        return redirect()->route('admin.delivery-slots.index', ['trashed' => 'with'])->with('success', 'Delivery slot restored successfully.');
    }
}
