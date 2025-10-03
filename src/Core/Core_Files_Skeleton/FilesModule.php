<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files;

use TMT\CRM\Core\Files\Infrastructure\Providers\FilesServiceProvider;
use TMT\CRM\Core\Files\Infrastructure\Migrations\FilesMigrator;

final class FilesModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_files_version';

    public static function bootstrap(): void
    {
        // Đăng ký service provider
        FilesServiceProvider::register();

        // Kiểm tra và migrate DB
        add_action('plugins_loaded', [FilesMigrator::class, 'maybe_install']);
    }
}
