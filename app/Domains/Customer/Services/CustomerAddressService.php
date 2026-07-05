<?php

namespace App\Domains\Customer\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerAddressService
{
    public function create(Customer $customer, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data) {
            $data['customer_id'] = $customer->id;
            $data['status'] = (bool) ($data['status'] ?? true);
            $data['is_default'] = (bool) ($data['is_default'] ?? false);
            $data['is_approved'] = (bool) ($data['is_approved'] ?? false);

            if ($customer->addresses()->count() === 0) {
                $data['is_default'] = true;
            }

            if ($data['is_default']) {
                $this->clearDefault($customer);
            }

            return CustomerAddress::query()->create($data);
        });
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data) {
            $data['status'] = (bool) ($data['status'] ?? $address->status);
            $data['is_default'] = (bool) ($data['is_default'] ?? $address->is_default);

            if ($data['is_default']) {
                $this->clearDefault($address->customer, $address->id);
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
            $this->clearDefault($address->customer, $address->id);
            $address->update(['is_default' => true]);

            return $address;
        });
    }

    public function approve(CustomerAddress $address, bool $approved): CustomerAddress
    {
        $address->update(['is_approved' => $approved]);

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
}
