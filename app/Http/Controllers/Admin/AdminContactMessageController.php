<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

class AdminContactMessageController extends Controller
{
    public function index()
    {
        return view('admin.contact-messages.index', [
            'messages' => ContactMessage::query()->latest()->paginate(20),
        ]);
    }
}
