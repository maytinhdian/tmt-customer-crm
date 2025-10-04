<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files;

use TMT\CRM\Core\Files\Infrastructure\Providers\FilesServiceProvider;
use TMT\CRM\Core\Files\Infrastructure\Migrations\FileMigrator;

/**
 * FilesModule (file chính)
 * - Bootstrap service/provider
 * - Kiểm tra & migrate DB
 */
final class FilesModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_files_version';

    public static function bootstrap(): void
    {
        // Đăng ký DI cho storage/repository
        FilesServiceProvider::register();

        // Migrate DB khi plugin khởi động
        add_action('plugins_loaded', [FileMigrator::class, 'maybe_install']);
    }
}
