<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Core\Events\Application\Services\EventDispatcher;
use TMT\CRM\Core\Events\Application\Services\StoringEventBus;
use TMT\CRM\Domain\Repositories\EventStoreRepositoryInterface;

/**
 * Provider cho Core/Events:
 * - Bind EventBusInterface -> StoringEventBus(EventDispatcher, EventStoreRepo)
 */
final class EventsServiceProvider
{
    public static function register(): void
    {
        Container::set(EventBusInterface::class, static function () {
            $inner = new EventDispatcher();
            /** @var EventStoreRepositoryInterface $store */
            $store = Container::get(EventStoreRepositoryInterface::class);
            return new StoringEventBus($inner, $store);
        });

        /**
         * Cho phép các module khác (Notifications/Log/…) đăng ký subscriber
         * càng sớm càng tốt.
         */
        if (function_exists('do_action')) {
            do_action('tmt_crm_events_ready');
        }
    }
}
