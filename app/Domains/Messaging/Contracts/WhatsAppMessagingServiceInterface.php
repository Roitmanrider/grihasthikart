<?php

namespace App\Domains\Messaging\Contracts;

use App\Models\PendingOrder;

interface WhatsAppMessagingServiceInterface
{
    public function configured(): bool;

    public function sendCartReminder(PendingOrder $pendingOrder, int $remainingMinutes): WhatsAppMessageResult;
}
