<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

use TMT\CRM\Shared\Logging\Writers\FileLogWriter;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;

final class ChannelLoggerFactory
{
    /**
     * Tạo Logger cho 1 channel với min_level & targets riêng.
     * @param string $channel   vd: 'customer','notifications','events',...
     * @param string $min_level vd: debug|info|warning|error|critical
     * @param string $targets   'file'|'database'|'both'
     */
    public static function make(string $channel, string $min_level, string $targets = 'file'): Logger
    {
        $writers = [];

        if ($targets === 'file' || $targets === 'both') {
            $writers[] = FileLogWriter::factory($channel);
        }

        if ($targets === 'database' || $targets === 'both') {
            $writers[] = static function (string $level, string $message, array $context) use ($channel): void {
                /** @var LogRepositoryInterface $repo */
                $repo = Container::get(LogRepositoryInterface::class);
                $repo->insert(
                    level: $level,
                    message: $message,
                    context: $context,
                    channel: $channel, // CHỐT channel tại đây
                    user_id: get_current_user_id(),
                    ip: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                    module: $context['module'] ?? $channel,
                    request_id: $context['request_id'] ?? (function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(8)))
                );
            };
        }

        return new Logger($min_level, ...$writers);
    }
}
