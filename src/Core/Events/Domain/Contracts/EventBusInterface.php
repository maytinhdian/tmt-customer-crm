<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Domain\Contracts;

interface EventBusInterface
{
    public function publish(EventInterface $event): void;
    public function subscribe(string $event_name, EventSubscriberInterface $subscriber, int $priority = 10): void;
}
