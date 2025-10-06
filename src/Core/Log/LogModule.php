<?php

/**
 * Core/Log bootstrap (file chính)
 */

declare(strict_types=1);

namespace TMT\CRM\Core\Log;

use TMT\CRM\Core\Log\Infrastructure\Persistence\WpdbLogRepository;
use TMT\CRM\Core\Log\Presentation\Admin\Screen\LogScreen;
use TMT\CRM\Core\Log\Presentation\Admin\Settings\LoggingSettingsIntegration;
use TMT\CRM\Core\Log\Presentation\Admin\Settings\LoggingChannelsSettingsIntegration;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Logging\LoggerInterface;
use TMT\CRM\Shared\Logging\Logger;
use TMT\CRM\Shared\Logging\LogLevel;
use TMT\CRM\Shared\Logging\Writers\FileLogWriter;
use TMT\CRM\Core\Log\Infrastructure\Setup\Installer;   // BỎ comment

final class LogModule
{
    public const VERSION        = '1.0.0';
    public const OPTION_VERSION = 'tmt_crm_core_log_version';
    public const CRON_HOOK      = 'tmt_crm_log_retention_daily';

    /**
     * Khởi động module Core/Log:
     * - Đăng ký Settings section
     * - Bind Repository + Logger theo Settings (Container::set)
     * - Đăng ký màn hình Logs
     * - Lên lịch retention job
     */
    public static function bootstrap(): void
    {

        // Đăng ký Settings sections
        LoggingSettingsIntegration::register();
        LoggingChannelsSettingsIntegration::register();

        // Đăng ký services sau khi WP load plugins
        add_action('plugins_loaded', static function () {
            global $wpdb;

            // 1. Bind repository DB cho log
            Container::set(
                LogRepositoryInterface::class,
                static function () use ($wpdb) {
                    return new WpdbLogRepository($wpdb);
                }
            );

            // 2. Bind Logger mặc định + multi-channel
            self::bind_logger_from_settings();
            self::bind_channel_loggers_from_settings();

            // 3. Đăng ký menu admin Logs
            LogScreen::register_menu();

            // 4. Lên lịch cron retention
            self::schedule_retention();
        }, 15);
    }

    /**
     * Đọc cấu hình từ Settings, tạo Logger mặc định (app)
     */
    private static function bind_logger_from_settings(): void
    {
        // Đọc settings
        $logging = null;
        if (class_exists('\TMT\CRM\Core\Settings\Settings')) {
            $logging = \TMT\CRM\Core\Settings\Settings::get('logging', null);
        }

        // Fallback option
        if ($logging === null) {
            $all_opts = get_option('tmt_crm_settings', []);
            $logging  = (isset($all_opts['logging']) && is_array($all_opts['logging'])) ? $all_opts['logging'] : null;
        }

        // Giá trị mặc định
        $min_level = isset($logging['min_level']) ? (string)$logging['min_level'] : LogLevel::INFO;
        $targets   = isset($logging['channel'])   ? (string)$logging['channel']   : 'file'; // file|database|both

        // Chuẩn hoá min_level
        if (!isset(LogLevel::map()[$min_level])) {
            $min_level = LogLevel::INFO;
        }

        // Writers
        $file_writer = FileLogWriter::factory('app');
        $db_writer = static function (string $level, string $message, array $context): void {
            /** @var LogRepositoryInterface $repo */
            $repo = Container::get(LogRepositoryInterface::class);
            try {
                $repo->insert(
                    $level,
                    $message,
                    $context,
                    'app',
                    get_current_user_id(),
                    $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                    $context['module'] ?? 'core',
                    $context['request_id'] ?? (function_exists('wp_generate_uuid4')
                        ? wp_generate_uuid4()
                        : bin2hex(random_bytes(8)))
                );
            } catch (\Throwable $e) {
                error_log('[TMT-CRM][DB-Writer] insert failed: ' . $e->getMessage());
            }
        };

        $writers = [$file_writer];
        if ($targets === 'database') {
            $writers = [$db_writer];
        } elseif ($targets === 'both') {
            $writers = [$file_writer, $db_writer];
        }

        // Bind LoggerInterface
        Container::set(
            LoggerInterface::class,
            static function () use ($min_level, $writers) {
                return new Logger($min_level, ...$writers);
            }
        );
    }

    /**
     * Bind logger theo từng channel (multi-channel)
     */
    private static function bind_channel_loggers_from_settings(): void
    {
        // Đọc cấu hình multi-channel
        $config = [];
        if (class_exists('\TMT\CRM\Core\Settings\Settings')) {
            $config = (array)\TMT\CRM\Core\Settings\Settings::get('logging', []);
        } else {
            $all_opts = get_option('tmt_crm_settings', []);
            $config   = (array)($all_opts['logging'] ?? []);
        }

        $channels = (array)($config['channels'] ?? [
            'customer'      => ['min_level' => 'info',    'targets' => 'both'],
            'notifications' => ['min_level' => 'warning', 'targets' => 'database'],
            'events'        => ['min_level' => 'debug',   'targets' => 'file'],
        ]);

        foreach ($channels as $name => $opts) {
            $channel   = is_string($name) && $name !== '' ? $name : 'app';
            $min_level = is_string($opts['min_level'] ?? null) ? $opts['min_level'] : LogLevel::INFO;
            $targets   = is_string($opts['targets']   ?? null) ? $opts['targets']   : 'file';

            // Bind logger cho từng kênh
            Container::set(
                'logger.' . $channel,
                static function () use ($channel, $min_level, $targets) {
                    return \TMT\CRM\Shared\Logging\ChannelLoggerFactory::make($channel, $min_level, $targets);
                }
            );
        }
    }

    /**
     * Cron: xoá log cũ (file + DB) theo keep_days
     */
    private static function schedule_retention(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
        add_action(self::CRON_HOOK, [self::class, 'run_retention']);
    }

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

        // Xoá FILE log cũ
        $upload_dir = wp_get_upload_dir();
        $dir = trailingslashit($upload_dir['basedir']) . 'tmt-crm/logs';
        if (is_dir($dir)) {
            $files = glob($dir . '/*.log');
            if (is_array($files)) {
                foreach ($files as $file) {
                    $mtime = @filemtime($file);
                    if ($mtime && (time() - $mtime) > $keep_days * DAY_IN_SECONDS) {
                        @unlink($file);
                    }
                }
            }
        }

        // Xoá DB log cũ
        try {
            /** @var LogRepositoryInterface $repo */
            $repo = Container::get(LogRepositoryInterface::class);
            $repo->purge_older_than_days($keep_days);
        } catch (\Throwable $e) {
            error_log('[TMT-CRM][Retention] purge failed: ' . $e->getMessage());
        }
    }
}
