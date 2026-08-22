<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminStoreContextController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'stock_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
        ]);

        if (empty($data['stock_location_id'])) {
            $request->session()->forget(AdminStoreContextService::SESSION_KEY);
        } else {
            $request->session()->put(AdminStoreContextService::SESSION_KEY, (int) $data['stock_location_id']);
        }

        return back()->with('success', 'Store context updated.');
    }
}
