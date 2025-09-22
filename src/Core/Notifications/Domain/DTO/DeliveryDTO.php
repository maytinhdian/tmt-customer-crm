<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class DeliveryDTO
{
    public function __construct(
        public ?int $id,
        public ?int $notification_id,
        public string $channel,      // 'notice' | 'email' | ...
        public ?int $recipient_id,
        public string $status,       // 'pending' | 'sent' | 'failed'
        public string $created_at
    ) {}
}
