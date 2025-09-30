<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Logging;

final class ContextSanitizer
{
    /** @param array<string,mixed> $context */
    public static function mask(array $context): array
    {
        $mask_keys = ['password','secret','token','api_key','license','key'];
        foreach ($mask_keys as $k) {
            if (array_key_exists($k, $context)) {
                $context[$k] = '***';
            }
        }
        return $context;
    }
}
