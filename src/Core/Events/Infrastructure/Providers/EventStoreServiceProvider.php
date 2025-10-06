<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\EventStoreRepositoryInterface;
use TMT\CRM\Core\Events\Infrastructure\Persistence\WpdbEventStoreRepository;

final class EventStoreServiceProvider
{
    public static function register(): void
    {
        Container::set(EventStoreRepositoryInterface::class, fn() => new WpdbEventStoreRepository($GLOBALS['wpdb']));
    }
}
