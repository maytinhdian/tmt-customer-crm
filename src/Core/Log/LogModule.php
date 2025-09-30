<?php
/**
 * Core/Log bootstrap (file chính)
 */
declare(strict_types=1);

namespace TMT\CRM\Core\Log;

use TMT\CRM\Core\Log\Infrastructure\Setup\Installer;
use TMT\CRM\Core\Log\Infrastructure\Persistence\WpdbLogRepository;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Logging\{Logger, LogLevel};
use TMT\CRM\Shared\Logging\Writers\FileLogWriter;
use TMT\CRM\Core\Log\Presentation\Admin\Screen\LogScreen;

final class LogModule
{
    public const VERSION = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_log_version';

    public static function register(): void
    {
        Installer::maybe_install();

        // Đăng ký repo + logger mặc định nếu chưa có
        add_action('plugins_loaded', static function () {
            global $wpdb;

            Container::set(LogRepositoryInterface::class, fn() => new WpdbLogRepository($wpdb));

           
                $min_level = LogLevel::INFO; // có thể lấy từ Core/Settings
                $fileWriter = FileLogWriter::factory('app');
                Container::set(\TMT\CRM\Shared\Logging\LoggerInterface::class, 
                    fn() => new Logger($min_level, $fileWriter)
                );
          
            // Đăng ký màn hình Admin
            LogScreen::register_menu();
        });
    }
}
