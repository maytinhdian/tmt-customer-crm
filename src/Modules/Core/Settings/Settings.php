<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Settings;

final class Settings
{
    /** option name trong wp_options */
    public const OPTION_KEY = 'tmt_crm_settings';

    /** Cache local */
    private static array $settings = [];

    /** Lấy toàn bộ settings */
    public static function all(): array
    {
        if (empty(self::$settings)) {
            $stored = get_option(self::OPTION_KEY, []);
            self::$settings = is_array($stored) ? $stored : [];
        }
        return self::$settings;
    }

    /** Lấy 1 giá trị setting */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    /** Ghi lại setting */
    public static function update(array $new): void
    {
        $merged = array_merge(self::all(), $new);
        update_option(self::OPTION_KEY, $merged);
        self::$settings = $merged;
    }
}
