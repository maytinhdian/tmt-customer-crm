<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Notifications\Infrastructure\Providers\NotificationsServiceProvider;
use TMT\CRM\Core\Notifications\Application\Services\NotificationDispatcher;
use TMT\CRM\Core\Notifications\Presentation\Subscribers\EventDefaultSubscriber;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;

final class NotificationsModule
{
    public static function boot(): void
    {
        // 1) Bind DI cho Notifications
        NotificationsServiceProvider::register();

        // 2) Sau khi DI sẵn sàng, đăng ký subscriber
        add_action('plugins_loaded', static function () {
            $bus        = \TMT\CRM\Shared\Container\Container::get(EventBusInterface::class);
            $dispatcher = \TMT\CRM\Shared\Container\Container::get(NotificationDispatcher::class);

            $bus->subscribe('CompanyCreated', new EventDefaultSubscriber($dispatcher), 10);
        }, 20);

       
    }
}
