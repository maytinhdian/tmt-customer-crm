<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Events\Application\Services\EventDispatcher;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;

final class EventsServiceProvider
{
    public static function register(): void
    {
        Container::set(EventBusInterface::class, fn () => new EventDispatcher());
    }
}
