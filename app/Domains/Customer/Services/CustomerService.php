<?php

namespace App\Domains\Customer\Services;

use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Models\Customer;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function create(array $data): Customer
    {
        return $this->repository->create($this->normalize($data));
    }

    public function update(Customer $customer, array $data): Customer
    {
        return $this->repository->update($customer, $this->normalize($data));
    }

    public function delete(Customer $customer): bool
    {
        return $this->repository->delete($customer);
    }

    public function restore(int $id): Customer
    {
        $customer = $this->repository->findWithTrashed($id);
        $customer->restore();

        return $customer;
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function normalize(array $data): array
    {
        foreach (['status', 'is_premium', 'cashback_enabled'] as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }

        return $data;
    }
}
