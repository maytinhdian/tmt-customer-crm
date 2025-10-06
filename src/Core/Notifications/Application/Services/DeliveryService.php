<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class DeliveryService
{
    /** @param array<string,ChannelAdapterInterface> $channels */
    public function __construct(private array $channels) {}

    /** @param array<string,mixed> $rendered */
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        $channel_id = $delivery->channel;
        if (!isset($this->channels[$channel_id])) {
            error_log('[Notifications] Channel not found: ' . $channel_id);
            return false;
        }
        return $this->channels[$channel_id]->send($delivery, $rendered);
    }
}
