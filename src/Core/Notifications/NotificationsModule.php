<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications;

use TMT\CRM\Core\Notifications\Presentation\Admin\Screen\NotificationCenterScreen;
use TMT\CRM\Core\Notifications\Presentation\Admin\Screen\SettingsScreen;
use TMT\CRM\Core\Notifications\Application\Services\NotificationDispatcher;
use TMT\CRM\Core\Notifications\Domain\EventKeys;
use TMT\CRM\Core\Notifications\Domain\DTO\EventContextDTO;
use TMT\CRM\Shared\EventBus;             // ← dùng wrapper mới
use TMT\CRM\Shared\Container\Container;            // DI container của dự án (nếu có)

use TMT\CRM\Domain\Repositories\NotificationRepositoryInterface;
use TMT\CRM\Core\Notifications\Infrastructure\Repositories\DbNotificationRepository;

use TMT\CRM\Domain\Repositories\DeliveryRepositoryInterface;
use TMT\CRM\Core\Notifications\Infrastructure\Repositories\DbDeliveryRepository;

use TMT\CRM\Domain\Repositories\TemplateRepositoryInterface;
use TMT\CRM\Core\Notifications\Infrastructure\Repositories\DbTemplateRepository;

use TMT\CRM\Domain\Repositories\PreferenceRepositoryInterface;
use TMT\CRM\Core\Notifications\Infrastructure\Repositories\DbPreferenceRepository;

final class NotificationsModule
{
    /** Gọi 1 lần ở bootstrap plugin (file chính) */
    public static function register(): void
    {
        NotificationCenterScreen::register();
        SettingsScreen::register();

        Container::set(NotificationRepositoryInterface::class, fn() => new DbNotificationRepository($GLOBALS['wpdb']));
        Container::set(DeliveryRepositoryInterface::class, fn() => new DbDeliveryRepository($GLOBALS['wpdb']));
        Container::set(TemplateRepositoryInterface::class, fn() => new DbTemplateRepository($GLOBALS['wpdb']));
        Container::set(PreferenceRepositoryInterface::class, fn() => new DbPreferenceRepository($GLOBALS['wpdb']));

        // CompanyCreated
        EventBus::listen(EventKeys::COMPANY_CREATED, function (EventContextDTO $ctx) {
            /** @var NotificationDispatcher $dispatcher */
            $dispatcher = Container::get(NotificationDispatcher::class);
            $dispatcher->on_event(EventKeys::COMPANY_CREATED, $ctx);
        });

        // CompanySoftDeleted
        EventBus::listen(EventKeys::COMPANY_SOFT_DELETED, function (EventContextDTO $ctx) {
            $dispatcher = Container::get(NotificationDispatcher::class);
            $dispatcher->on_event(EventKeys::COMPANY_SOFT_DELETED, $ctx);
        });

        // QuoteSent
        EventBus::listen(EventKeys::QUOTE_SENT, function (EventContextDTO $ctx) {
            $dispatcher = Container::get(NotificationDispatcher::class);
            $dispatcher->on_event(EventKeys::QUOTE_SENT, $ctx);
        });


        // \TMT\CRM\Core\Notifications\Infrastructure\Seeder\NotificationsSeeder::seed();

    }
}
