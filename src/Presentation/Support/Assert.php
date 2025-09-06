<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Assert
{
    public static function not_empty(mixed $v, string $message): void
    {
        if (empty($v) && $v !== '0') {
            throw new \InvalidArgumentException($message);
        }
    }

    public static function greater_than_zero(int $v, string $message): void
    {
        if ($v <= 0) {
            throw new \InvalidArgumentException($message);
        }
    }
}
