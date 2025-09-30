<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Logging\Writers;

final class FileLogWriter
{
    /** @return callable(string,string,array<string,mixed>):void */
    public static function factory(string $channel = 'app'): callable
    {
        return static function (string $level, string $message, array $context) use ($channel): void {
            $upload_dir = wp_get_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'tmt-crm/logs';
            if (!is_dir($dir)) { wp_mkdir_p($dir); }

            $file = $dir . '/app-' . gmdate('Y-m-d') . '.log';
            $line = sprintf(
                "[%s] %s.%s %s %s\n",
                gmdate('c'),
                strtoupper($level),
                $channel,
                $message,
                $context ? json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : ''
            );
            // @phpstan-ignore-next-line
            file_put_contents($file, $line, FILE_APPEND);
        };
    }
}
