<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Settings;

final class SettingsRegistry
{
    /** @var SettingsSectionInterface[] */
    private static array $sections = [];

    /** Module gọi để thêm section của mình */
    public static function add_section(SettingsSectionInterface $section): void
    {
        self::$sections[$section->section_id()] = $section;
    }

    /** Core page sẽ lấy danh sách section để render/đăng ký */
    public static function sections(): array
    {
        /**
         * Cho phép module đăng ký section qua filter.
         * Mỗi module có thể push instance SettingsSectionInterface vào mảng.
         */
        $sections = apply_filters('tmt_crm_settings_sections', []);
        foreach ($sections as $sec) {
            if ($sec instanceof SettingsSectionInterface) {
                self::add_section($sec);
            }
        }
        return array_values(self::$sections);
    }

    /** Gom default của tất cả section */
    public static function collect_defaults(): array
    {
        $defaults = [];
        foreach (self::sections() as $sec) {
            $defaults = array_merge($defaults, $sec->get_defaults());
        }
        return $defaults;
    }

    /**
     * Chạy sanitize cho từng section, merge kết quả theo thứ tự section.
     * $input_all: toàn bộ input post lên (mảng).
     * $current_all: toàn bộ setting hiện có (mảng).
     */
    public static function sanitize_all(array $input_all, array $current_all): array
    {
        $result = $current_all;
        foreach (self::sections() as $sec) {
            $sanitized = $sec->sanitize($input_all, $result);
            if (is_array($sanitized)) {
                $result = array_merge($result, $sanitized);
            }
        }
        return $result;
    }
}
