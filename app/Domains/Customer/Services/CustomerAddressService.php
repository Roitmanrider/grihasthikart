<?php

namespace App\Domains\Customer\Services;

use App\Domains\Notification\Services\NotificationService;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerAddressService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function create(Customer $customer, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data) {
            $data['customer_id'] = $customer->id;
            $data['status'] = (bool) ($data['status'] ?? true);
            $data['is_approved'] = (bool) ($data['is_approved'] ?? false);
            $data['approval_status'] = $data['is_approved'] ? 'APPROVED' : 'PENDING';
            $data['approval_status_changed_at'] = now();
            $data['rejection_reason'] = null;
            $requestedDefault = (bool) ($data['is_default'] ?? false);

            $data['is_default'] = $data['is_approved']
                && ($requestedDefault || ! $customer->addresses()->where('is_default', true)->exists());

            if ($data['is_default']) {
                $this->clearDefault($customer);
            }

            return CustomerAddress::query()->create($data);
        });
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data) {
            $significantChanged = $address->is_approved && $this->changesDeliveryData($address, $data);
            $data['status'] = (bool) ($data['status'] ?? $address->status);
            $data['is_default'] = (bool) ($data['is_default'] ?? $address->is_default);

            if ($significantChanged) {
                $data['is_approved'] = false;
                $data['approval_status'] = 'PENDING';
                $data['rejection_reason'] = null;
                $data['approval_status_changed_at'] = now();
                $data['is_default'] = false;
            }

            if ($data['is_default']) {
                if (! $address->is_approved && ! ($data['is_approved'] ?? false)) {
                    throw new InvalidArgumentException('Only approved addresses can be set as default.');
                }

                $this->clearDefault($address->customer, $address->id);
            }

            $address->update($data);

            return $address;
        });
    }

    public function createByAdmin(Customer $customer, array $data): CustomerAddress
    {
        $data['is_approved'] = (bool) ($data['is_approved'] ?? true);
        $data['status'] = (bool) ($data['status'] ?? true);

        return $this->create($customer, $data);
    }

    public function updateByAdmin(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data) {
            $data['status'] = (bool) ($data['status'] ?? $address->status);
            $data['is_default'] = (bool) ($data['is_default'] ?? $address->is_default);
            $data['is_approved'] = (bool) ($data['is_approved'] ?? $address->is_approved);
            $data['approval_status'] = $data['is_approved'] ? 'APPROVED' : ($data['rejection_reason'] ?? null ? 'REJECTED' : 'PENDING');
            $data['rejection_reason'] = $data['is_approved'] ? null : ($data['rejection_reason'] ?? $address->rejection_reason);
            $data['approval_status_changed_at'] = now();

            if ($data['is_default']) {
                if (! $data['is_approved'] || ! $data['status']) {
                    throw new InvalidArgumentException('Only approved active addresses can be set as default.');
                }

                $this->clearDefault($address->customer, $address->id);
            }

            if (! $data['is_approved'] || ! $data['status']) {
                $data['is_default'] = false;
            }

            $address->update($data);

            return $address;
        });
    }

    public function delete(CustomerAddress $address): bool
    {
        return (bool) $address->delete();
    }

    public function setDefault(CustomerAddress $address): CustomerAddress
    {
        return DB::transaction(function () use ($address) {
            if (! $address->is_approved || ! $address->status) {
                throw new InvalidArgumentException('Only approved active addresses can be set as default.');
            }

            $this->clearDefault($address->customer, $address->id);
            $address->update(['is_default' => true]);

            return $address;
        });
    }

    public function approve(CustomerAddress $address, bool $approved, ?string $reason = null): CustomerAddress
    {
        $wasApproved = (bool) $address->is_approved;
        $address->update([
            'is_approved' => $approved,
            'approval_status' => $approved ? 'APPROVED' : ($reason ? 'REJECTED' : 'PENDING'),
            'rejection_reason' => $approved ? null : $reason,
            'approval_status_changed_at' => now(),
            'is_default' => $approved ? $address->is_default : false,
        ]);

        if ($wasApproved !== $approved || $reason) {
            $this->notificationService->notifyCustomerAddressApprovalChanged($address->fresh('customer'), $approved);
        }

        return $address;
    }

    public function ensureBelongsToCustomer(Customer $customer, CustomerAddress $address): void
    {
        if ($address->customer_id !== $customer->id) {
            throw new InvalidArgumentException('This address does not belong to the current customer.');
        }
    }

    private function clearDefault(Customer $customer, ?int $exceptId = null): void
    {
        $query = $customer->addresses()->where('is_default', true);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update(['is_default' => false]);
    }

    private function changesDeliveryData(CustomerAddress $address, array $data): bool
    {
        foreach (['recipient_name', 'mobile', 'address_line1', 'address_line2', 'city', 'state', 'pincode', 'landmark', 'latitude', 'longitude', 'geofence_radius_meters'] as $field) {
            if (array_key_exists($field, $data) && (string) ($data[$field] ?? '') !== (string) ($address->{$field} ?? '')) {
                return true;
            }
        }

        return false;
    }
}
