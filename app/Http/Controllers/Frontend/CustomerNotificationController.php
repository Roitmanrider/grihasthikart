<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use InvalidArgumentException;

class CustomerNotificationController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService
    ) {}

    public function index()
    {
        $customer = $this->requireCustomer();
        $notifications = Notification::query()
            ->forCustomer($customer)
            ->latest()
            ->paginate(20);

        return view('frontend.customer.notifications.index', compact('customer', 'notifications'));
    }

    public function read(Notification $notification)
    {
        $customer = $this->requireCustomer();

        abort_unless(
            $notification->audience === Notification::AUDIENCE_CUSTOMER
            && (int) $notification->customer_id === (int) $customer->id,
            404
        );

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll()
    {
        $customer = $this->requireCustomer();

        Notification::query()
            ->forCustomer($customer)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function requireCustomer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
