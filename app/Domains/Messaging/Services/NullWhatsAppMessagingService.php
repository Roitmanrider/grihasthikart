<?php

namespace App\Domains\Messaging\Services;

use App\Domains\Messaging\Contracts\WhatsAppMessageResult;
use App\Domains\Messaging\Contracts\WhatsAppMessagingServiceInterface;
use App\Models\PendingOrder;

class NullWhatsAppMessagingService implements WhatsAppMessagingServiceInterface
{
    public function configured(): bool
    {
        return false;
    }

    public function sendCartReminder(PendingOrder $pendingOrder, int $remainingMinutes): WhatsAppMessageResult
    {
        return WhatsAppMessageResult::failed('NOT_CONFIGURED', 'WhatsApp messaging provider is not configured.');
    }
}
