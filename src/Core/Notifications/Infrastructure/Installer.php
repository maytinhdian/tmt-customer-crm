<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure;

final class Installer
{
    public static function target_version(): string
    {
        return '1.0.0';
    }

    public function install_or_upgrade(string $from_version): void
    {
        // TODO: tạo bảng crm_notifications, crm_notification_deliveries, crm_notification_templates, crm_notification_preferences
    }
}
