<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Log
{
    public static function info(string $msg): void
    {
        error_log('[TMT CRM] ' . $msg);
    }

    public static function debug_array(string $title, array $data): void
    {
        error_log('[TMT CRM] ' . $title . ': ' . wp_json_encode($data));
    }
}
