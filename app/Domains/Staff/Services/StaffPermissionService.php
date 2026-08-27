<?php

namespace App\Domains\Staff\Services;

use App\Models\User;

class StaffPermissionService
{
    public const ROLE_BUNDLES = [
        'STORE_MANAGER' => [
            'orders.view',
            'orders.confirm',
            'picking.view',
            'picking.assign',
            'packing.view',
            'packing.assign',
            'delivery.view',
            'delivery.assign',
            'cart_followup.view',
            'cart_followup.manage',
            'approvals.view',
            'approvals.return_to_store',
            'approvals.delivery_override',
            'approvals.inventory_adjustment',
        ],
        'INVENTORY_STAFF' => [
            'inventory.view',
            'picking.view',
            'picking.start',
            'picking.complete',
            'packing.view',
            'packing.start',
            'packing.complete',
        ],
        'PICKER_PACKER' => [
            'picking.view',
            'picking.start',
            'picking.complete',
            'packing.view',
            'packing.start',
            'packing.complete',
        ],
        'DELIVERY_AGENT' => [
            'delivery.view',
            'delivery.start',
            'delivery.mark_delivered',
            'delivery.mark_failed',
            'delivery.mark_customer_unavailable',
            'delivery.request_reschedule',
            'delivery.request_return_to_store',
            'delivery.request_override',
        ],
        'CART_FOLLOW_UP_EMPLOYEE' => [
            'cart_followup.view',
            'cart_followup.manage',
        ],
    ];

    public const APPROVAL_PERMISSIONS = [
        'approvals.return_to_store' => 'Returned-to-store approval',
        'approvals.delivery_override' => 'Delivery override approval',
        'approvals.inventory_adjustment' => 'Inventory adjustment approval',
    ];

    public function roles(): array
    {
        return [
            'STORE_MANAGER' => 'Store Manager',
            'INVENTORY_STAFF' => 'Inventory Staff',
            'PICKER_PACKER' => 'Picker / Packer',
            'DELIVERY_AGENT' => 'Delivery Agent',
            'CART_FOLLOW_UP_EMPLOYEE' => 'Cart Follow-up Employee',
        ];
    }

    public function allPermissions(): array
    {
        return collect(self::ROLE_BUNDLES)
            ->flatten()
            ->merge([
                'inventory.adjust',
                'delivery.override_review',
                'orders.override',
            ])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function permissionsFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->allPermissions();
        }

        $roles = $user->staff_roles ?: ($user->role ? [$user->role] : []);
        $bundlePermissions = collect($roles)
            ->flatMap(fn (string $role) => self::ROLE_BUNDLES[$role] ?? [])
            ->merge($user->additional_permissions ?? [])
            ->unique()
            ->diff($user->denied_permissions ?? [])
            ->values()
            ->all();

        return $bundlePermissions;
    }

    public function has(User $user, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($user), true);
    }

    public function isOperationalStaff(User $user): bool
    {
        return ! empty($user->staff_roles)
            || in_array($user->role, array_keys(self::ROLE_BUNDLES), true);
    }

    public function canAccessStore(User $user, ?int $stockLocationId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $stockLocationId !== null && (int) $user->assigned_store_id === (int) $stockLocationId;
    }
}
