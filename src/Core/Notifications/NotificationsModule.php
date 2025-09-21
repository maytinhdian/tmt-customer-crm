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

final class NotificationsModule
{
    /** Gọi 1 lần ở bootstrap plugin (file chính) */
    public static function register(): void
    {
        NotificationCenterScreen::register();
        SettingsScreen::register();

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
    }
}
