<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Settings;

use TMT\CRM\Shared\View;

final class SettingsPage
{
    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function add_menu(): void
    {
        add_submenu_page(
            'tmt-crm', // parent slug
            __('Cài đặt CRM', 'tmt-crm'),
            __('Cài đặt', 'tmt-crm'),
            'manage_options',
            'tmt-crm-settings',
            [self::class, 'render']
        );
    }

    public static function register_settings(): void
    {
        $option_key = Settings::OPTION_KEY;

        // Lấy defaults từ tất cả section (kể cả module) và merge vào options hiện tại.
        $current = Settings::all();
        $with_defaults = array_merge(SettingsRegistry::collect_defaults(), $current);
        if ($with_defaults !== $current) {
            update_option($option_key, $with_defaults);
        }

        register_setting(
            'tmt_crm_settings_group',
            $option_key,
            [
                'sanitize_callback' => function ($input) use ($option_key) {
                    $input_arr = is_array($input) ? $input : [];
                    $current   = Settings::all();
                    $sanitized = SettingsRegistry::sanitize_all($input_arr, $current);
                    return is_array($sanitized) ? $sanitized : $current;
                }
            ]
        );

        // Section "Cài đặt chung" (ví dụ chung)
        add_settings_section(
            'general',
            __('Cài đặt chung', 'tmt-crm'),
            fn() => print('<p>' . esc_html__('Các thiết lập cơ bản cho CRM', 'tmt-crm') . '</p>'),
            'tmt-crm-settings'
        );

        // Field mẫu: per_page
        add_settings_field(
            'per_page',
            __('Số bản ghi / trang', 'tmt-crm'),
            [self::class, 'render_per_page_field'],
            'tmt-crm-settings',
            'general'
        );

        // Cho từng section của module tự đăng ký field
        foreach (SettingsRegistry::sections() as $section) {
            add_settings_section(
                $section->section_id(),
                esc_html($section->section_title()),
                fn() => null,
                'tmt-crm-settings'
            );
            $section->register_fields('tmt-crm-settings', $option_key);
        }
    }

    public static function render(): void
    {
        // Luôn dùng View::render_admin_module() (quy ước dự án)
        View::render_admin_module('core/settings-page', [
            'option_key' => Settings::OPTION_KEY,
        ]);
    }

    public static function render_per_page_field(): void
    {
        $value = (int) Settings::get('per_page', 20);
        echo '<input type="number" min="5" max="200" name="' . esc_attr(Settings::OPTION_KEY) . '[per_page]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . esc_html__('Áp dụng mặc định khi chưa cấu hình Per Page ở Screen Options.', 'tmt-crm') . '</p>';
    }
}
