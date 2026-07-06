<?php

namespace App\Domains\Delivery\Services;

use App\Domains\Delivery\Contracts\DeliverySlotRepositoryInterface;
use App\Models\DeliverySlot;
use Carbon\Carbon;
use InvalidArgumentException;

class DeliverySlotService
{
    public function __construct(
        private readonly DeliverySlotRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function activeSlots()
    {
        return $this->repository->activeSlots();
    }

    public function activeSlotsForDate(?string $deliveryDate = null)
    {
        $slots = $this->activeSlots();

        if (! $deliveryDate) {
            return $slots;
        }

        $date = Carbon::parse($deliveryDate, config('app.timezone'))->startOfDay();

        if (! $date->isSameDay(now(config('app.timezone')))) {
            return $slots;
        }

        $currentTime = now(config('app.timezone'))->format('H:i:s');

        return $slots
            ->filter(fn (DeliverySlot $slot): bool => $this->normalizeTime($slot->end_time) > $currentTime)
            ->values();
    }

    public function isSlotAvailableForDate(string $deliverySlot, ?string $deliveryDate = null): bool
    {
        return $this->activeSlotsForDate($deliveryDate)
            ->contains(fn (DeliverySlot $slot): bool => $slot->label === $deliverySlot);
    }

    public function create(array $data): DeliverySlot
    {
        $this->validateTimes($data);
        $this->validateOverlap($data);

        return $this->repository->create($this->normalize($data));
    }

    public function update(DeliverySlot $slot, array $data): DeliverySlot
    {
        $this->validateTimes($data);
        $this->validateOverlap($data, $slot->id);

        return $this->repository->update($slot, $this->normalize($data));
    }

    public function delete(DeliverySlot $slot): bool
    {
        return $this->repository->delete($slot);
    }

    public function restore(int $id): DeliverySlot
    {
        $slot = $this->repository->findWithTrashed($id);
        $slot->restore();

        return $slot;
    }

    private function validateTimes(array $data): void
    {
        if (($data['start_time'] ?? '') >= ($data['end_time'] ?? '')) {
            throw new InvalidArgumentException('Delivery slot start time must be before end time.');
        }
    }

    private function validateOverlap(array $data, ?int $ignoreId = null): void
    {
        if (($data['status'] ?? true) && $this->repository->hasOverlap($data['start_time'], $data['end_time'], $ignoreId)) {
            throw new InvalidArgumentException('Active delivery slots cannot overlap.');
        }
    }

    private function normalize(array $data): array
    {
        $data['status'] = (bool) ($data['status'] ?? false);
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
