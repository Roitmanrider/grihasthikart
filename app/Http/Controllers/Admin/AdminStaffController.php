<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    public function index()
    {
        return view('admin.staff.index', [
            'staff' => User::query()->with('assignedStore')->orderBy('name')->paginate(20),
        ]);
    }

    public function edit(User $staff)
    {
        return view('admin.staff.edit', [
            'staff' => $staff,
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
            'roles' => $this->roles(),
        ]);
    }

    public function update(User $staff, Request $request)
    {
        $data = $request->validate([
            'role' => ['nullable', Rule::in(array_keys($this->roles()))],
            'assigned_store_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where('status', true)],
        ]);

        if (($data['role'] ?? null) === 'SUPER_ADMIN') {
            $data['assigned_store_id'] = null;
        } elseif (! empty($data['role']) && empty($data['assigned_store_id'])) {
            return back()->withInput()->withErrors(['assigned_store_id' => 'Assign a store for store staff roles.']);
        }

        $staff->update($data);

        return redirect()->route('admin.staff.index')->with('success', 'Staff permissions updated.');
    }

    private function roles(): array
    {
        return [
            'SUPER_ADMIN' => 'Super Admin',
            'STORE_MANAGER' => 'Store Manager',
            'CART_FOLLOW_UP_EMPLOYEE' => 'Cart Follow-up Employee',
        ];
    }
}
