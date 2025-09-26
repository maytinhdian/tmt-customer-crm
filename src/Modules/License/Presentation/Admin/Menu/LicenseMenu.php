<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Menu;

use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen;

/** Khai báo menu & screen cho License (P0: khung). */
final class LicenseMenu
{
    public static function register(): void
    {
        // add_menu_page(...); -> callback LicenseScreen::render_list()
    }
}
