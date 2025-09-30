<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

interface LoggerInterface
{
    /** @param array<string,mixed> $context */
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;

    /** @param array<string,mixed> $context */
    public function log(string $level, string $message, array $context = []): void;
}
