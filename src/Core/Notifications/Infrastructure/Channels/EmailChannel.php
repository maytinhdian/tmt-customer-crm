<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class EmailChannel implements ChannelInterface
{
    public function id(): string { return 'email'; }

    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        // TODO: wp_mail(...) sau khi đọc cấu hình từ Core/Settings
        return true;
    }
}
