<?php

namespace App\Domains\Messaging\Contracts;

class WhatsAppMessageResult
{
    public function __construct(
        public readonly bool $sent,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null
    ) {}

    public static function sent(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId);
    }

    public static function failed(string $failureCode, ?string $failureMessage = null): self
    {
        return new self(false, null, $failureCode, $failureMessage);
    }
}
