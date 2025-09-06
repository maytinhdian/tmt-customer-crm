<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Arr
{
    public static function get(array $a, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $a) ? $a[$key] : $default;
    }

    public static function only(array $a, array $keys): array
    {
        return array_intersect_key($a, array_flip($keys));
    }
}
