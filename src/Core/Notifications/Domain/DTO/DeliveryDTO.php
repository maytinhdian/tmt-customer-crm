<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain\DTO;

final class DeliveryDTO
{
    /**
     * @param array<int|string,mixed> $recipients
     * @param array<string,mixed> $meta
     */
    public function __construct(
        public string $channel,
        public array $recipients = [],
        public array $meta = []
    ) {}
}
