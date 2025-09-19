<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications;

use TMT\CRM\Core\Notifications\Presentation\Admin\Screen\NotificationCenterScreen;
use TMT\CRM\Core\Notifications\Presentation\Admin\Screen\SettingsScreen;

/** Bootstrap (file chính) của Core/Notifications */
final class NotificationsModule
{
    /** Gọi 1 lần ở bootstrap plugin */
    public static function register(): void
    {
        NotificationCenterScreen::register();
        SettingsScreen::register();
        // TODO: listen sự kiện domain qua EventBus và trỏ về NotificationDispatcher::on_event()
    }
}
