<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Presentation\Admin\Screen;

final class SettingsScreen
{
    public const PAGE_SLUG = 'tmt-crm-notifications-settings';

    public static function register(): void
    {
        // add_submenu_page(...) dưới trang Settings trung tâm
    }
}
