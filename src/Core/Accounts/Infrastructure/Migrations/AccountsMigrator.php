<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Infrastructure\Migrations;

final class AccountsMigrator
{
    public const OPTION_VERSION = 'tmt_crm_accounts_version';
    public const VERSION = '1.0.0';

    public static function maybe_install(): void
    {
        $installed = get_option(self::OPTION_VERSION);
        if ($installed === self::VERSION) {
            return;
        }
        // Không tạo bảng vì dùng wp_users/usermeta + user_meta cho preferences.
        update_option(self::OPTION_VERSION, self::VERSION, true);
    }
}
