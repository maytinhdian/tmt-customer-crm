<?php

/**
 * Core/Log bootstrap (file chính)
 */

declare(strict_types=1);

namespace TMT\CRM\Core\Log;

use TMT\CRM\Core\Log\Infrastructure\Setup\Installer;
use TMT\CRM\Core\Log\Infrastructure\Persistence\WpdbLogRepository;
use TMT\CRM\Core\Log\Presentation\Admin\Screen\LogScreen;
use TMT\CRM\Core\Log\Presentation\Admin\Settings\LoggingSettingsIntegration;

use TMT\CRM\Domain\Repositories\LogRepositoryInterface;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Shared\Logging\Logger;
use TMT\CRM\Shared\Logging\LogLevel;
use TMT\CRM\Shared\Logging\Writers\FileLogWriter;

final class LogModule
{
    public const VERSION        = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_log_version';
    public const CRON_HOOK      = 'tmt_crm_log_retention_daily';

    /**
     * Khởi động module Core/Log:
     * - Tạo bảng
     * - Đăng ký Settings section "Logging"
     * - Bind Repository + Logger theo Settings (Container::set)
     * - Đăng ký màn hình Logs
     * - Lên lịch retention job
     */
    public static function register(): void
    {
        // 1) Cài đặt DB (tạo bảng nếu cần)
        Installer::maybe_install();

        // 2) Đăng ký section Settings "Logging" (chuẩn SettingsSectionInterface)
        LoggingSettingsIntegration::register();

        // 3) Đăng ký services sau khi WP load plugins
        add_action('plugins_loaded', static function () {
            global $wpdb;

            // 3.1) Bind repository DB cho log (static set)
            Container::set(
                LogRepositoryInterface::class,
                fn() => new  WpdbLogRepository($wpdb)
            );

            // 3.2) Bind LoggerInterface dựa trên Core/Settings (static set)
            self::bind_logger_from_settings();

            // 3.3) Đăng ký màn hình Admin đọc log từ DB
            LogScreen::register_menu();

            // 3.4) Lên lịch job retention (xóa log cũ file + DB)
            self::schedule_retention();
        }, 15);
    }

    /**
     * Đọc cấu hình từ Core/Settings (hoặc option fallback) và set LoggerInterface.
     * Channel: file|database|both
     * Min level: debug|info|warning|error|critical
     */
    private static function bind_logger_from_settings(): void
    {
        // Đọc settings
        $logging = null;

        // Nếu module Core/Settings có mặt, ưu tiên dùng
        if (class_exists('\TMT\CRM\Core\Settings\Settings')) {
            /** @var array{channel?:string,min_level?:string,keep_days?:int}|null $logging */
            $logging = \TMT\CRM\Core\Settings\Settings::get('logging', null);
        }

        // Fallback lấy từ option chung nếu chưa có
        if ($logging === null) {
            $all_opts = get_option('tmt_crm_settings', []);
            $logging  = (isset($all_opts['logging']) && is_array($all_opts['logging'])) ? $all_opts['logging'] : null;
        }

        // Default an toàn
        $min_level = isset($logging['min_level']) ? (string)$logging['min_level'] : LogLevel::INFO;
        $channel   = isset($logging['channel'])   ? (string)$logging['channel']   : 'file';

        // Chuẩn hóa min_level theo map
        $map = LogLevel::map();
        if (!isset($map[$min_level])) {
            $min_level = LogLevel::INFO;
        }

        // Writer FILE (ghi vào uploads/tmt-crm/logs/app-YYYY-MM-DD.log)
        $file_writer = FileLogWriter::factory('app');

        // Writer DATABASE (ghi vào bảng tmt_crm_logs qua repository)
        $db_writer = static function (string $level, string $message, array $context): void {
            /** @var LogRepositoryInterface $repo */
            $repo = Container::get(LogRepositoryInterface::class);
            $repo->insert(
                $level,
                $message,
                $context,
                'app',
                get_current_user_id(),
                isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? (string)$_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null),
                isset($context['module']) ? (string)$context['module'] : 'core',
                isset($context['request_id'])
                    ? (string)$context['request_id']
                    : (function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(8)))
            );
        };

        // Chọn danh sách writers theo channel (PHP 7 compatible)
        $writers = [$file_writer]; // default file
        if ($channel === 'database') {
            $writers = [$db_writer];
        } elseif ($channel === 'both') {
            $writers = [$file_writer, $db_writer];
        }

        // Set instance LoggerInterface vào Container
        Container::set(
            LoggerInterface::class,
            fn() => new Logger($min_level, ...$writers)
        );
    }

    /**
     * Lên lịch cron retention nếu chưa có, và gắn handler.
     * Xoá log quá hạn theo "keep_days" trong Settings.
     */
    private static function schedule_retention(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
        add_action(self::CRON_HOOK, [self::class, 'run_retention']);
    }

    /**
     * Cron handler: xoá log cũ (file + DB) theo keep_days.
     * - File: wp-content/uploads/tmt-crm/logs/app-*.log
     * - DB: gọi LogRepositoryInterface::purge_older_than_days()
     */
    public static function run_retention(): void
    {
        // Lấy keep_days từ Settings
        $keep_days = 30;

        if (class_exists('\TMT\CRM\Core\Settings\Settings')) {
            $logging = \TMT\CRM\Core\Settings\Settings::get('logging', ['keep_days' => 30]);
            $keep_days = isset($logging['keep_days']) ? (int)$logging['keep_days'] : 30;
        } else {
            $all_opts  = get_option('tmt_crm_settings', []);
            $keep_days = isset($all_opts['logging']['keep_days']) ? (int)$all_opts['logging']['keep_days'] : 30;
        }

        if ($keep_days <= 0) {
            return;
        }

        // 1) Xoá FILE log cũ
        $upload_dir = wp_get_upload_dir();
        $dir = trailingslashit($upload_dir['basedir']) . 'tmt-crm/logs';
        if (is_dir($dir)) {
            $files = glob($dir . '/app-*.log');
            if (is_array($files)) {
                foreach ($files as $file) {
                    $mtime = @filemtime($file);
                    if ($mtime && (time() - $mtime) > $keep_days * DAY_IN_SECONDS) {
                        @unlink($file);
                    }
                }
            }
        }

        // 2) Xoá DB log cũ
        try {
            /** @var LogRepositoryInterface $repo */
            $repo = Container::get(LogRepositoryInterface::class);
            $repo->purge_older_than_days($keep_days);
        } catch (\Throwable $e) {
            // Không làm chết cron
            error_log('[TMT-CRM][Retention] purge failed: ' . $e->getMessage());
        }
    }
}
