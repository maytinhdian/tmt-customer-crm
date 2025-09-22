<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Contracts;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

interface ChannelAdapterInterface
{
    /**
     * @param array<string,mixed> $rendered  payload đã render (subject/body/…)
     */
    public function send(DeliveryDTO $delivery, array $rendered): bool;
}
