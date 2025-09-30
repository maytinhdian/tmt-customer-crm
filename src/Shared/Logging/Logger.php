<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

/**
 * Logger đa writer: file/db,…, tôn trọng min_level từ Settings.
 */
final class Logger implements LoggerInterface
{
    /** @var array<int, callable(string,string,array<string,mixed>):void> */
    private array $writers = [];

    private string $min_level = LogLevel::INFO;
    private int $min_level_num;

    public function __construct(string $min_level, callable ...$writers)
    {
        $this->min_level = $min_level;
        $map = LogLevel::map();
        $this->min_level_num = $map[$min_level] ?? 200;

        foreach ($writers as $w) {
            $this->writers[] = $w;
        }
    }

    /** @param array<string,mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $map = LogLevel::map();
        if (($map[$level] ?? 0) < $this->min_level_num) {
            return;
        }

        // mask dữ liệu nhạy cảm
        $context = ContextSanitizer::mask($context);

        foreach ($this->writers as $writer) {
            try {
                $writer($level, $message, $context);
            } catch (\Throwable $e) {
                // Không làm chết request
                error_log('[TMT-CRM][Logger] writer failed: ' . $e->getMessage());
            }
        }
    }

    public function debug(string $message, array $context = []): void { $this->log(LogLevel::DEBUG, $message, $context); }
    public function info(string $message, array $context = []): void { $this->log(LogLevel::INFO, $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log(LogLevel::WARNING, $message, $context); }
    public function error(string $message, array $context = []): void { $this->log(LogLevel::ERROR, $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log(LogLevel::CRITICAL, $message, $context); }
}
