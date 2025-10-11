<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\Settings;
use TMT\CRM\Core\Settings\SettingsSectionInterface;

/**
 * AccountSettingIntegration
 * - TÍCH HỢP THEO CHUẨN SettingsRegistry (section), KHÔNG dùng cơ chế "tab" cũ.
 * - Đăng ký section qua filter tmt_crm_settings_sections.
 */
final class AccountSettingIntegration implements SettingsSectionInterface
{
    /** Gọi 1 lần ở bootstrap của AccountsModule */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    public function section_id(): string
    {
        return 'accounts';
    }

    public function section_title(): string
    {
        return __('Tài khoản & Tuỳ chọn', 'tmt-crm');
    }
    public function capability(): string
    {
        return 'manage_options';
    }
    public function header_html(): string
    {
        return '<p>' . esc_html__('Cấu hình quản lý Log Chanel.', 'tmt-crm') . '</p>';
    }
    /**
     * Đăng ký các field cho section.
     * Lưu ý: SettingsPage đã add_settings_section($this->section_id()) sẵn,
     * nên ở đây chỉ cần add_settings_field các input cụ thể.
     */
    public function register_fields(string $page_slug, string $option_key): void
    {
        // =========================
        // ====== USER PICKER ======
        // =========================
        add_settings_field(
            'accounts_sep_picker',
            '',
            function (): void {
                echo '<div class="tmt-settings-sep" style="margin:16px 0 8px;">';
                echo '<h3 style="margin:0 0 6px;">' . esc_html__('Cài đặt User Picker', 'tmt-crm') . '</h3>';
                echo '<hr style="margin:6px 0;">';
                echo '</div>';
            },
            $page_slug,
            $this->section_id()
        );

        // must_cap cho AJAX picker
        add_settings_field(
            'accounts_picker_must_cap',
            __('Capability yêu cầu', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_picker_must_cap', 'list_users');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[accounts_picker_must_cap]" value="' . esc_attr($v) . '" />';
                echo '<p class="description">' . esc_html__('Ví dụ: list_users, edit_others_posts...', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // per_page cho AJAX picker
        add_settings_field(
            'accounts_picker_per_page',
            __('Số dòng mỗi trang (AJAX)', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (int) Settings::get('accounts_picker_per_page', 20);
                echo '<input type="number" min="1" max="100" name="' . esc_attr($option_key) . '[accounts_picker_per_page]" value="' . esc_attr((string) $v) . '" />';
            },
            $page_slug,
            $this->section_id()
        );

        // label template cho picker
        add_settings_field(
            'accounts_picker_label_tpl',
            __('Mẫu hiển thị nhãn', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_picker_label_tpl', '{display_name} — {email}');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[accounts_picker_label_tpl]" value="' . esc_attr($v) . '" />';
                echo '<p class="description">' . esc_html__('Placeholder: {display_name}, {user_login}, {email}, {id}', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // meta key cho phone (khớp với repo hiện tại là owner_phone)
        add_settings_field(
            'accounts_phone_meta_key',
            __('User meta key cho SĐT', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_phone_meta_key', 'owner_phone');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[accounts_phone_meta_key]" value="' . esc_attr($v) . '" />';
                echo '<p class="description">' . esc_html__('Gợi ý chuẩn hoá: tmt_phone (sau này đổi đồng bộ repo).', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // =========================
        // == DEFAULT PREFERENCES ==
        // =========================
        add_settings_field(
            'accounts_sep_prefs',
            '',
            function (): void {
                echo '<div class="tmt-settings-sep" style="margin:16px 0 8px;">';
                echo '<h3 style="margin:0 0 6px;">' . esc_html__('Mặc định User Preferences', 'tmt-crm') . '</h3>';
                echo '<hr style="margin:6px 0;">';
                echo '</div>';
            },
            $page_slug,
            $this->section_id()
        );

        // timezone mặc định (áp dụng nơi cần đọc default)
        add_settings_field(
            'accounts_default_timezone',
            __('Timezone mặc định', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_default_timezone', '');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[accounts_default_timezone]" value="' . esc_attr($v) . '" placeholder="Asia/Ho_Chi_Minh" />';
            },
            $page_slug,
            $this->section_id()
        );

        // locale mặc định
        add_settings_field(
            'accounts_default_locale',
            __('Locale mặc định', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_default_locale', 'vi');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[accounts_default_locale]" value="' . esc_attr($v) . '" placeholder="vi|en_US|vi_VN" />';
            },
            $page_slug,
            $this->section_id()
        );

        // kênh thông báo mặc định
        add_settings_field(
            'accounts_notifications_channel',
            __('Kênh thông báo mặc định', 'tmt-crm'),
            function () use ($option_key): void {
                $v = (string) Settings::get('accounts_notifications_channel', 'email');
                $ops = [
                    'email' => __('Email', 'tmt-crm'),
                    'screen' => __('Trong màn hình (admin notice)', 'tmt-crm'),
                    'both' => __('Cả hai', 'tmt-crm'),
                ];
                echo '<select name="' . esc_attr($option_key) . '[accounts_notifications_channel]">';
                foreach ($ops as $k => $label) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($k),
                        selected($v, $k, false),
                        esc_html($label)
                    );
                }
                echo '</select>';
            },
            $page_slug,
            $this->section_id()
        );
    }

    /** Defaults cho section */
    public function get_defaults(): array
    {
        return [
            // User Picker
            'accounts_picker_must_cap'   => 'list_users',
            'accounts_picker_per_page'   => 20,
            'accounts_picker_label_tpl'  => '{display_name} — {email}',
            'accounts_phone_meta_key'    => 'owner_phone',
            // Default Preferences
            'accounts_default_timezone'        => '',
            'accounts_default_locale'          => 'vi',
            'accounts_notifications_channel'   => 'email', // email|screen|both
        ];
    }

    /** Sanitize dữ liệu của section */
    public function sanitize(array $input, array $current_all): array
    {
        $out = [];

        // User Picker
        if (array_key_exists('accounts_picker_must_cap', $input)) {
            $v = sanitize_text_field((string) $input['accounts_picker_must_cap']);
            $out['accounts_picker_must_cap'] = mb_substr($v, 0, 64);
        }
        if (array_key_exists('accounts_picker_per_page', $input)) {
            $v = (int) $input['accounts_picker_per_page'];
            $out['accounts_picker_per_page'] = max(1, min(100, $v));
        }
        if (array_key_exists('accounts_picker_label_tpl', $input)) {
            $v = sanitize_text_field((string) $input['accounts_picker_label_tpl']);
            $out['accounts_picker_label_tpl'] = mb_substr($v, 0, 120);
        }
        if (array_key_exists('accounts_phone_meta_key', $input)) {
            $v = sanitize_key((string) $input['accounts_phone_meta_key']);
            $out['accounts_phone_meta_key'] = mb_substr($v, 0, 64);
        }

        // Default Preferences
        if (array_key_exists('accounts_default_timezone', $input)) {
            $v = sanitize_text_field((string) $input['accounts_default_timezone']);
            $out['accounts_default_timezone'] = mb_substr($v, 0, 64);
        }
        if (array_key_exists('accounts_default_locale', $input)) {
            $v = sanitize_text_field((string) $input['accounts_default_locale']);
            $out['accounts_default_locale'] = mb_substr($v, 0, 32);
        }
        if (array_key_exists('accounts_notifications_channel', $input)) {
            $v = (string) $input['accounts_notifications_channel'];
            $allow = ['email', 'screen', 'both'];
            $out['accounts_notifications_channel'] = in_array($v, $allow, true) ? $v : 'email';
        }

        // Khác với Numbering, phần Accounts không cần sync DTO qua repository riêng.
        // Các giá trị này dùng để:
        // - Cấu hình AJAX User Picker (must_cap, per_page, label_tpl, phone_meta_key)
        // - Làm default cho PreferenceService khi user chưa set cá nhân.

        return $out;
    }
}
