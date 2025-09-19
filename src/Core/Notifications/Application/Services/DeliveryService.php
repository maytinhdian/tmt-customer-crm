<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class DeliveryService
{
    /** @param array<string, object> $channels map channel id => adapter */
    public function __construct(private array $channels, private \TMT\CRM\Domain\Repositories\DeliveryRepositoryInterface $deliveries) {}

    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        $channel_id = $delivery->channel; // 'notice' | 'email' | ...
        if (!isset($this->channels[$channel_id])) {
            return false;
        }
        $ok = $this->channels[$channel_id]->send($delivery, $rendered);
        $this->deliveries->update_status($delivery->id, $ok ? 'sent' : 'failed', $ok ? null : 'send_error');
        return $ok;
    }
}
