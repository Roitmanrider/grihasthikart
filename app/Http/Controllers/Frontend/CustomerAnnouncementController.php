<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Marketing\Services\CustomerAnnouncementService;
use App\Http\Controllers\Controller;
use App\Models\CustomerAnnouncement;
use Illuminate\Http\Request;

class CustomerAnnouncementController extends Controller
{
    public function dismiss(CustomerAnnouncement $announcement, Request $request, CustomerAuthService $authService, CustomerAnnouncementService $service)
    {
        $customer = $authService->currentCustomer($request->session());
        abort_unless($customer, 403);

        $service->dismiss($announcement, $customer);

        return back()->with('success', 'Announcement dismissed.');
    }
}
