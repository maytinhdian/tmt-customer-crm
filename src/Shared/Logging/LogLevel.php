<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

final class LogLevel
{
    public const DEBUG    = 'debug';
    public const INFO     = 'info';
    public const WARNING  = 'warning';
    public const ERROR    = 'error';
    public const CRITICAL = 'critical';

    /** @return array<string,int> */
    public static function map(): array
    {
        return [
            self::DEBUG => 100,
            self::INFO => 200,
            self::WARNING => 300,
            self::ERROR => 400,
            self::CRITICAL => 500,
        ];
    }
}
