<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

use TMT\CRM\Core\Settings\Settings;
use TMT\CRM\Shared\Logging\Writers\FileLogWriter;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;

final class ChannelLoggerFactory
{
    /**
     * @param string $channel   vd: 'customer','notifications','events',...
     * @param string $fallback_min_level khi không có cấu hình
     * @param string $fallback_targets   'file'|'database'|'both'|'none'
     */
    public static function make(
        string $channel,
        string $fallback_min_level = LogLevel::INFO,
        string $fallback_targets   = 'both'
    ): LoggerInterface {
        $settings = Settings::get('logging', [
            'min_level' => $fallback_min_level,
            'keep_days' => 30,
            'channel'   => 'app',
            'channels'  => [],
        ]);

        // Ưu tiên cấu hình riêng theo channel
        $per = $settings['channels'][$channel] ?? null;
        $min = is_array($per) && !empty($per['min_level'])
            ? (string) $per['min_level']
            : ((string)($settings['min_level'] ?? $fallback_min_level));

        $targets = is_array($per) && !empty($per['targets'])
            ? (string) $per['targets']
            : $fallback_targets;

        $writers = [];

        if ($targets === 'file' || $targets === 'both') {
            $writers[] = FileLogWriter::factory($channel);
        }

        if ($targets === 'database' || $targets === 'both') {
            /** @var LogRepositoryInterface $repo */
            $repo = Container::get(LogRepositoryInterface::class);

            $writers[] = static function (string $level, string $message, array $context) use ($repo, $channel): void {
                $repo->insert(
                    level: $level,
                    message: $message,
                    context: $context,
                    channel: $channel,
                    user_id: function_exists('get_current_user_id') ? (int) get_current_user_id() : null,
                    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                    module: $context['module'] ?? $channel,
                    request_id: $context['request_id']
                        ?? (function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(8)))
                );
            };
        }

        if ($targets === 'none' || empty($writers)) {
            // Logger “rỗng” (chặn log)
            return new Logger(LogLevel::CRITICAL); // set rất cao để effectively tắt
        }

        return new Logger($min, ...$writers);
    }
}
