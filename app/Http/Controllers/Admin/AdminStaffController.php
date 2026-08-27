<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Staff\Services\StaffPermissionService;
use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminStaffController extends Controller
{
    public function __construct(
        private readonly StaffPermissionService $staffPermissions
    ) {}

    public function index()
    {
        return view('admin.staff.index', [
            'staff' => User::query()->with('assignedStore')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.staff.edit', $this->formData(new User, true));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        User::query()->create($data);

        return redirect()->route('admin.staff.index')->with('success', 'Staff account created.');
    }

    public function edit(User $staff)
    {
        return view('admin.staff.edit', $this->formData($staff));
    }

    public function update(User $staff, Request $request)
    {
        $staff->update($this->validated($request, false, $staff));

        return redirect()->route('admin.staff.index')->with('success', 'Staff permissions updated.');
    }

    private function formData(User $staff, bool $creating = false): array
    {
        return [
            'staff' => $staff,
            'creating' => $creating,
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
            'roles' => $this->roles(),
            'staffRoles' => $this->staffPermissions->roles(),
            'allPermissions' => $this->staffPermissions->allPermissions(),
            'approvalPermissions' => StaffPermissionService::APPROVAL_PERMISSIONS,
        ];
    }

    private function validated(Request $request, bool $creating, ?User $staff = null): array
    {
        $data = $request->validate([
            'name' => [$creating ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['nullable', Rule::in(array_keys($this->roles()))],
            'staff_roles' => ['nullable', 'array'],
            'staff_roles.*' => [Rule::in(array_keys($this->staffPermissions->roles()))],
            'additional_permissions' => ['nullable', 'array'],
            'additional_permissions.*' => [Rule::in($this->staffPermissions->allPermissions())],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => [Rule::in($this->staffPermissions->allPermissions())],
            'assigned_store_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where('status', true)],
            'staff_active' => ['nullable', 'boolean'],
        ]);

        if (($data['role'] ?? null) === 'SUPER_ADMIN') {
            $data['assigned_store_id'] = null;
        } elseif ((! empty($data['role']) || ! empty($data['staff_roles'])) && empty($data['assigned_store_id'])) {
            throw ValidationException::withMessages(['assigned_store_id' => 'Assign a store for store staff roles.']);
        }

        $data['staff_roles'] = array_values(array_unique($data['staff_roles'] ?? []));
        $data['additional_permissions'] = array_values(array_unique($data['additional_permissions'] ?? []));
        $data['denied_permissions'] = array_values(array_unique($data['denied_permissions'] ?? []));
        $data['staff_active'] = (bool) ($data['staff_active'] ?? false);
        $data['staff_approved_at'] = $data['staff_roles'] ? now() : null;
        $data['staff_approved_by'] = $data['staff_roles'] ? $request->user()?->id : null;

        if (! $creating) {
            unset($data['name'], $data['email']);

            if (empty($data['password'])) {
                unset($data['password']);
            }
        }

        return $data;
    }

    private function roles(): array
    {
        return [
            'SUPER_ADMIN' => 'Super Admin',
            'STORE_MANAGER' => 'Store Manager',
            'INVENTORY_STAFF' => 'Inventory Staff',
            'PICKER_PACKER' => 'Picker / Packer',
            'DELIVERY_AGENT' => 'Delivery Agent',
            'CART_FOLLOW_UP_EMPLOYEE' => 'Cart Follow-up Employee',
        ];
    }
}
