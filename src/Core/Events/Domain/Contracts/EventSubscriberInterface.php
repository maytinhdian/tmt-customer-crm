<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Domain\Contracts;

interface EventSubscriberInterface
{
    /** @return array<string,int> [event_name => priority] */
    public static function subscribed_events(): array;
    public function handle(EventInterface $event): void;
}
