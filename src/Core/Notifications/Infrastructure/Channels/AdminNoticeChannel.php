<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class AdminNoticeChannel implements ChannelInterface
{
    public function id(): string { return 'notice'; }

    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        // MVP: rely vào Presentation để render từ deliveries (chưa push realtime)
        return true;
    }
}
