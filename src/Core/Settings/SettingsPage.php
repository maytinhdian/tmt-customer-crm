<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Settings;

use TMT\CRM\Shared\Presentation\Support\View;

final class SettingsPage
{
    public const MENU_SLUG      = 'tmt-crm-settings';
    public const SETTINGS_GROUP = 'tmt_crm_settings_group';
    public const PAGE_CAP       = 'manage_options';
    public const PARENT_SLUG    = 'tmt-crm';

    /** ID section mặc định của “Cài đặt chung” */
    private const GENERAL_SECTION_ID = 'general';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'add_menu'], 20);
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

        $current  = Settings::all(); // [] nếu chưa có
        $defaults = SettingsRegistry::collect_defaults(); // ví dụ: ['per_page' => 20]

        if ($current === []) {
            // Chỉ tạo lần đầu
            add_option($option_key, is_array($defaults) ? $defaults : []);
        } else {
            // Chỉ bổ sung key MỚI, không ghi đè giá trị đang có
            // (toán tử + sẽ giữ nguyên $current nếu key đã tồn tại)
            $with_defaults = $current + (is_array($defaults) ? $defaults : []);
            if ($with_defaults !== $current) {
                update_option($option_key, $with_defaults);
            }
        }

        register_setting(
            'tmt_crm_settings_group',
            $option_key,
            [
                'type'              => 'array',
                'sanitize_callback' => [self::class, 'sanitize_all'],
                'default'           => [],
                'show_in_rest'      => false,

            ]
        );

        // // Section "Cài đặt chung" (ví dụ chung)
        // add_settings_section(
        //     'general',
        //     __('Cài đặt chung', 'tmt-crm'),
        //     fn() => print('<p>' . esc_html__('Các thiết lập cơ bản cho CRM', 'tmt-crm') . '</p>'),
        //     'tmt-crm-settings'
        // );

        // Section TAB: General (core)
        add_settings_section(
            self::GENERAL_SECTION_ID,
            __('Cài đặt chung', 'tmt-crm'),
            static function (): void {
                echo '<p>' . esc_html__('Các thiết lập cơ bản cho CRM', 'tmt-crm') . '</p>';
            },
            self::MENU_SLUG
        );

        // // Field mẫu: per_page
        // add_settings_field(
        //     'per_page',
        //     __('Số bản ghi / trang', 'tmt-crm'),
        //     [self::class, 'render_per_page_field'],
        //     'tmt-crm-settings',
        //     'general'
        // );

        // Field mẫu: per_page
        add_settings_field(
            'per_page',
            __('Số bản ghi / trang', 'tmt-crm'),
            [self::class, 'render_per_page_field'],
            self::MENU_SLUG,
            self::GENERAL_SECTION_ID,
            ['label_for' => 'tmt_crm_per_page']
        );

        // // Cho từng section của module tự đăng ký field
        // foreach (SettingsRegistry::sections() as $section) {
        //     add_settings_section(
        //         $section->section_id(),
        //         esc_html($section->section_title()),
        //         fn() => null,
        //         'tmt-crm-settings'
        //     );
        //     $section->register_fields('tmt-crm-settings', $option_key);
        // }

        // Đăng ký sections cho từng module (mỗi module = một TAB)
        foreach (SettingsRegistry::sections() as $section) {
            add_settings_section(
                $section->section_id(),
                esc_html($section->section_title()),
                static function () use ($section): void {
                    $desc = method_exists($section, 'header_html') ? (string)$section->header_html() : '';
                    if ($desc !== '') {
                        echo $desc; // section tự chịu trách nhiệm escape
                    }
                },
                self::MENU_SLUG
            );
            $section->register_fields(self::MENU_SLUG, $option_key);
        }
    }
    public static function sanitize_all($input): array
    {
        $in  = is_array($input) ? $input : [];
        $out = Settings::all(); // copy giá trị hiện có

        // per_page (GENERAL)
        if (array_key_exists('per_page', $in)) {
            $pp = (int) $in['per_page'];
            $out['per_page'] = max(5, min(200, $pp)); // ghi đè hợp lệ
        }

        // Các section/module khác do Registry quản
        $out = SettingsRegistry::sanitize_all($in, $out);

        return $out; // PHẢI return mảng mới để WP update_option()
    }

    // public static function render(): void
    // {

    //     // Luôn dùng View::render_admin_module() (quy ước dự án)
    //     View::render_admin_module('core', 'settings-page', [
    //         'option_key' => Settings::OPTION_KEY,
    //     ]);
    // }
    public static function render(): void
    {
        if (!current_user_can(self::PAGE_CAP)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'tmt-crm'));
        }

        // Chuẩn bị danh sách TAB: General + các module sections
        $tabs = [
            [
                'id'    => self::GENERAL_SECTION_ID,
                'title' => __('Cài đặt chung', 'tmt-crm'),
            ],
        ];
        foreach (SettingsRegistry::sections() as $sec) {
            $tabs[] = [
                'id'    => $sec->section_id(),
                'title' => $sec->section_title(),
            ];
        }

        // Tab đang chọn (mặc định: tab đầu tiên)
        $active = isset($_GET['tab']) ? sanitize_key((string)$_GET['tab']) : ($tabs[0]['id'] ?? self::GENERAL_SECTION_ID);
        $tab_ids = array_column($tabs, 'id');
        if (!in_array($active, $tab_ids, true)) {
            $active = $tabs[0]['id'] ?? self::GENERAL_SECTION_ID;
        }

        View::render_admin_module('core', 'settings-page-tabs', [
            'option_key'     => Settings::OPTION_KEY,
            'menu_slug'      => self::MENU_SLUG,
            'settings_group' => self::SETTINGS_GROUP,
            'tabs'           => $tabs,
            'active_tab'     => $active,
        ]);
    }


    public static function render_per_page_field(): void
    {
        $value = (int) Settings::get('per_page', 10);
        echo '<input type="number" min="5" max="200" name="' . esc_attr(Settings::OPTION_KEY) . '[per_page]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . esc_html__('Áp dụng mặc định khi chưa cấu hình Per Page ở Screen Options.', 'tmt-crm') . '</p>';
    }
    /** URL builder cho tab (nếu bạn cần trong view) */
    public static function tab_url(string $tab): string
    {
        return esc_url(add_query_arg(['page' => self::MENU_SLUG, 'tab' => $tab], admin_url('admin.php')));
    }
}
