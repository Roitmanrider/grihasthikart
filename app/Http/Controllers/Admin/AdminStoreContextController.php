<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStoreContextController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user()?->canSwitchStoreContext(), 403);

        $data = $request->validate([
            'stock_location_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('status', true))],
        ]);

        if (empty($data['stock_location_id'])) {
            $request->session()->forget(AdminStoreContextService::SESSION_KEY);
        } else {
            $request->session()->put(AdminStoreContextService::SESSION_KEY, (int) $data['stock_location_id']);
        }

        return back()->with('success', 'Store context updated.');
    }
}
