<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class NotificationDTO
{
    public function __construct(
        public ?int $id,
        public string $event_key,
        public string $summary,
        public int $actor_id,
        public string $created_at
    ) {}
}
