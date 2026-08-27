<?php

namespace App\Domains\Staff\Services;

use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StaffNotificationService
{
    public function notify(User $recipient, string $workstream, string $eventType, string $title, ?string $message = null, ?Model $related = null, ?int $storeId = null, ?string $actionUrl = null, array $data = []): StaffNotification
    {
        return StaffNotification::query()->create([
            'recipient_user_id' => $recipient->id,
            'stock_location_id' => $storeId,
            'workstream' => $workstream,
            'event_type' => $eventType,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data ?: null,
        ]);
    }

    public function unreadCounts(User $user): Collection
    {
        return StaffNotification::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('workstream, COUNT(*) as total')
            ->groupBy('workstream')
            ->pluck('total', 'workstream');
    }

    public function markAllRead(User $user, ?string $workstream = null): int
    {
        return StaffNotification::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->when($workstream, fn ($query) => $query->where('workstream', $workstream))
            ->update(['read_at' => now()]);
    }
}
