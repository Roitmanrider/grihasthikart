<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::query()
            ->admin()
            ->latest()
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function read(Notification $notification)
    {
        abort_unless($notification->audience === Notification::AUDIENCE_ADMIN, 404);

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll()
    {
        Notification::query()
            ->admin()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
