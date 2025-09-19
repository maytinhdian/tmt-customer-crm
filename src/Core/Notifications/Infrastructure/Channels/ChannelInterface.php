<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

interface ChannelInterface
{
    public function id(): string; // 'notice' | 'email' | ...
    public function send(DeliveryDTO $delivery, array $rendered): bool;
}
