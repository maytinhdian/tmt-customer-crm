<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\Settings;
use TMT\CRM\Core\Settings\SettingsSectionInterface;

final class LicenseSettingsIntegration implements SettingsSectionInterface
{
    /**
     * (Gợi ý) Gọi 1 lần ở bootstrap của LicenseModule
     * để đăng ký section vào Settings qua filter tmt_crm_settings_sections.
     */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', static function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    public function section_id(): string
    {
        return 'licenses';
    }

    public function section_title(): string
    {
        return __('Licenses', 'tmt-crm');
    }

    public function get_defaults(): array
    {
        return [
            // số ngày trước khi hết hạn để cảnh báo
            'license_expiring_days' => 14,
            // hiện admin notice khi có license sắp hết hạn
            'license_notice_enabled' => 1,
        ];
    }
    public function capability(): string
    {
        return 'manage_options';
    }
    public function header_html(): string
    {
        return '<p>' . esc_html__('Cấu hình quản lý License.', 'tmt-crm') . '</p>';
    }

    public function register_fields(string $page_slug, string $option_key): void
    {
        // Lấy giá trị hiện tại (gộp default)
        $all   = get_option($option_key, []);
        $value = isset($all[$this->section_id()]) && is_array($all[$this->section_id()])
            ? array_merge($this->get_defaults(), $all[$this->section_id()])
            : $this->get_defaults();

        // Field: Expiring within (days)
        add_settings_field(
            'license_expiring_days',
            __('Expiring within (days)', 'tmt-crm'),
            function () use ($option_key) {
                $all = Settings::all();
                $val = (int)($all['license_expiring_days'] ?? 14);
                printf(
                    '<input type="number" min="1" name="%s[license_expiring_days]" value="%d" class="small-text"/>',
                    esc_attr($option_key),
                    $val
                );
                echo ' <span class="description">' . esc_html__('Số ngày trước hạn để hiển thị cảnh báo.', 'tmt-crm') . '</span>';
            },
            $page_slug,
            $this->section_id()
        );

        // Field: Admin Notice toggle
        add_settings_field(
            'license_notice_enabled',
            __('Admin Notice', 'tmt-crm'),
            function () use ($option_key) {
                $all = Settings::all();
                $val = !empty($all['license_notice_enabled']) ? 1 : 0;
                printf(
                    '<label><input type="checkbox" name="%s[license_notice_enabled]" value="1" %s/> %s</label>',
                    esc_attr($option_key),
                    checked(1, $val, false),
                    esc_html__('Hiện thông báo trên admin khi có license sắp hết hạn', 'tmt-crm')
                );
            },
            $page_slug,
            $this->section_id(),
            ['label_for' => 'tmt_crm_license_expiring_days']
        );
    }

    public function sanitize(array $input, array $current_all): array
    {
        $out = [];

        // lấy từ mảng tổng (option_key)
        $days  = isset($input['license_expiring_days']) ? (int)$input['license_expiring_days'] : ($current_all['license_expiring_days'] ?? 14);
        $days  = max(1, min(365, $days)); // clamp 1..365

        $notice = !empty($input['license_notice_enabled']) ? 1 : 0;

        $out['license_expiring_days']  = $days;
        $out['license_notice_enabled'] = $notice;

        return $out;
    }
}
