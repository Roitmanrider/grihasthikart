<?php

namespace App\Domains\Catalog\Contracts;

use App\Models\DailyOffer;
use Illuminate\Database\Eloquent\Model;

interface DailyOfferRepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function currentOffers(int $limit = 8);

    public function activeOfferExistsForVariant(int $productVariantId, ?int $ignoreId = null, mixed $startsAt = null, mixed $endsAt = null): bool;

    public function productVariantOptions();

    public function findWithTrashed(int $id): DailyOffer;

    public function create(array $data);

    public function update(Model|int $record, array $data);

    public function delete(Model|int $record);
}
