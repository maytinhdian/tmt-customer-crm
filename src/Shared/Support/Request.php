<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Support;

final class Request
{
    public static function get_int(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
    }

    public static function post_int(string $key, int $default = 0): int
    {
        return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
    }

    public static function get_bool(string $key, bool $default = false): bool
    {
        if (!isset($_GET[$key])) return $default;
        $v = $_GET[$key];
        return $v === '1' || $v === 1 || $v === 'true' || $v === true;
    }

    public static function get_string(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? sanitize_text_field((string) $_GET[$key]) : $default;
    }

    public static function post_string(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_text_field((string) $_POST[$key]) : $default;
    }
}
