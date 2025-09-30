<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\Settings;
use TMT\CRM\Core\Settings\SettingsSectionInterface;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\NumberingRepositoryInterface;
use TMT\CRM\Core\Numbering\Domain\DTO\NumberingRuleDTO;

/**
 * NumberingSettingsIntegration
 * - TÍCH HỢP THEO CHUẨN SettingsRegistry (section), KHÔNG dùng cơ chế "tab" cũ.
 * - Đăng ký section qua filter tmt_crm_settings_sections.
 */
final class NumberingSettingsIntegration implements SettingsSectionInterface
{
    /** Gọi 1 lần ở bootstrap của NumberingModule */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    public function section_id(): string
    {
        return 'numbering';
    }

    public function section_title(): string
    {
        return __('Đánh số tự động', 'tmt-crm');
    }

    /**
     * Đăng ký các field cho section.
     * Lưu ý: SettingsPage đã add_settings_section($this->section_id()) sẵn,
     * nên ở đây chỉ cần add_settings_field các input cụ thể.
     */
    public function register_fields(string $page_slug, string $option_key): void
    {
        // --- SEPARATOR: COMPANY ---
        add_settings_field(
            'numbering_sep_company',
            '', // để trống nhãn (cột trái)
            function () {
                echo '<div class="tmt-settings-sep" style="margin:16px 0 8px;">
                <h3 style="margin:0 0 6px;">' . esc_html__('Cài đặt đánh số tự động cho module COMPANY', 'tmt-crm') . '</h3>
                <hr style="margin:6px 0;">
              </div>';
            },
            $page_slug,
            $this->section_id()
        );
        // --- COMPANY: PREFIX ---
        add_settings_field(
            'numbering_company_prefix',
            __('Prefix mã Công ty', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_company_prefix', 'C-{year}-');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[numbering_company_prefix]" value="' . esc_attr($v) . '" />';
                echo '<p class="description">' . esc_html__('Hỗ trợ {year}, {yy}, {month}', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // --- COMPANY: SUFFIX ---
        add_settings_field(
            'numbering_company_suffix',
            __('Suffix mã Công ty', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_company_suffix', '');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[numbering_company_suffix]" value="' . esc_attr($v) . '" />';
            },
            $page_slug,
            $this->section_id()
        );

        // --- COMPANY: PADDING ---
        add_settings_field(
            'numbering_company_padding',
            __('Số chữ số (padding)', 'tmt-crm'),
            function () use ($option_key) {
                $v = (int) Settings::get('numbering_company_padding', 4);
                echo '<input type="number" min="1" max="10" name="' . esc_attr($option_key) . '[numbering_company_padding]" value="' . esc_attr((string)$v) . '" />';
            },
            $page_slug,
            $this->section_id()
        );

        // --- COMPANY: RESET MODE ---
        add_settings_field(
            'numbering_company_reset',
            __('Chính sách reset', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_company_reset', 'yearly');
                $ops = [
                    'never'   => __('Không reset', 'tmt-crm'),
                    'yearly'  => __('Theo năm', 'tmt-crm'),
                    'monthly' => __('Theo tháng', 'tmt-crm'),
                ];
                echo '<select name="' . esc_attr($option_key) . '[numbering_company_reset]">';
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

        // Gợi ý: nhân bản 4 field trên cho customer/contact/quote nếu cần (đổi tiền tố key).
        // =========================
        // ======== LICENSE ========
        // =========================

        // --- SEPARATOR: LICENSE ---
        add_settings_field(
            'numbering_sep_license',
            '', // để trống nhãn (cột trái)
            function () {
                echo '<div class="tmt-settings-sep" style="margin:16px 0 8px;">
                <h3 style="margin:0 0 6px;">' . esc_html__('Cài đặt đánh số tự động cho module LICENSE', 'tmt-crm') . '</h3>
                <hr style="margin:6px 0;">
              </div>';
            },
            $page_slug,
            $this->section_id()
        );

        // LICENSE: PREFIX
        add_settings_field(
            'numbering_license_prefix',
            __('Prefix mã License', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_license_prefix', 'LIC-{year}-');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[numbering_license_prefix]" value="' . esc_attr($v) . '" />';
                echo '<p class="description">' . esc_html__('Hỗ trợ {year}, {yy}, {month}', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );
        // LICENSE: SUFFIX
        add_settings_field(
            'numbering_license_suffix',
            __('Suffix mã License', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_license_suffix', '');
                echo '<input type="text" class="regular-text" name="' . esc_attr($option_key) . '[numbering_license_suffix]" value="' . esc_attr($v) . '" />';
            },
            $page_slug,
            $this->section_id()
        );
        // LICENSE: PADDING
        add_settings_field(
            'numbering_license_padding',
            __('Số chữ số (padding) License', 'tmt-crm'),
            function () use ($option_key) {
                $v = (int) Settings::get('numbering_license_padding', 5);
                echo '<input type="number" min="1" max="10" name="' . esc_attr($option_key) . '[numbering_license_padding]" value="' . esc_attr((string)$v) . '" />';
            },
            $page_slug,
            $this->section_id()
        );

        // LICENSE: RESET
        add_settings_field(
            'numbering_license_reset',
            __('Chính sách reset License', 'tmt-crm'),
            function () use ($option_key) {
                $v = (string) Settings::get('numbering_license_reset', 'yearly');
                $ops = [
                    'never'   => __('Không reset', 'tmt-crm'),
                    'yearly'  => __('Theo năm', 'tmt-crm'),
                    'monthly' => __('Theo tháng', 'tmt-crm'),
                ];
                echo '<select name="' . esc_attr($option_key) . '[numbering_license_reset]">';
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
            'numbering_company_prefix'  => 'C-{year}-',
            'numbering_company_suffix'  => '',
            'numbering_company_padding' => 4,
            'numbering_company_reset'   => 'yearly', // never|yearly|monthly
            // license
            'numbering_license_prefix'  => 'LIC-{year}-',
            'numbering_license_suffix'  => '',
            'numbering_license_padding' => 5,
            'numbering_license_reset'   => 'yearly', // never|yearly|monthly
        ];
    }

    /** Sanitize dữ liệu của section */
    public function sanitize(array $input, array $current_all): array
    {
        $out = [];

        // PREFIX
        if (array_key_exists('numbering_company_prefix', $input)) {
            $v = sanitize_text_field((string)$input['numbering_company_prefix']);
            $out['numbering_company_prefix'] = mb_substr($v, 0, 50);
        }

        // SUFFIX
        if (array_key_exists('numbering_company_suffix', $input)) {
            $v = sanitize_text_field((string)$input['numbering_company_suffix']);
            $out['numbering_company_suffix'] = mb_substr($v, 0, 50);
        }

        // PADDING
        if (array_key_exists('numbering_company_padding', $input)) {
            $v = (int)$input['numbering_company_padding'];
            $out['numbering_company_padding'] = max(1, min(10, $v));
        }

        // RESET
        if (array_key_exists('numbering_company_reset', $input)) {
            $v = (string)$input['numbering_company_reset'];
            $allow = ['never', 'yearly', 'monthly'];
            $out['numbering_company_reset'] = in_array($v, $allow, true) ? $v : 'never';
        }

        /************************
         * LICENSE 
         ***********************/
        // PREFIX
        if (array_key_exists('numbering_license_prefix', $input)) {
            $v = sanitize_text_field((string)$input['numbering_license_prefix']);
            $out['numbering_license_prefix'] = mb_substr($v, 0, 50);
        }

        // SUFFIX
        if (array_key_exists('numbering_license_suffix', $input)) {
            $v = sanitize_text_field((string)$input['numbering_license_suffix']);
            $out['numbering_license_suffix'] = mb_substr($v, 0, 50);
        }

        // PADDING
        if (array_key_exists('numbering_license_padding', $input)) {
            $v = (int)$input['numbering_license_padding'];
            $out['numbering_license_padding'] = max(1, min(10, $v));
        }

        // RESET
        if (array_key_exists('numbering_license_reset', $input)) {
            $v = (string)$input['numbering_license_reset'];
            $allow = ['never', 'yearly', 'monthly'];
            $out['numbering_license_reset'] = in_array($v, $allow, true) ? $v : 'never';
        }


        // --- SYNC sang DB rule (ví dụ cho license) ---
        /** @var NumberingRepositoryInterface $repo */
        $repo = Container::get(NumberingRepositoryInterface::class);

        $company = new NumberingRuleDTO(
            entity_type: 'company',
            prefix: $out['numbering_company_prefix']  ?? ($current_all['numbering_company_prefix']  ?? 'C-{year}-'),
            suffix: $out['numbering_company_suffix']  ?? ($current_all['numbering_company_suffix']  ?? ''),
            padding: $out['numbering_company_padding'] ?? (int)($current_all['numbering_company_padding'] ?? 5),
            reset: $out['numbering_company_reset']   ?? ($current_all['numbering_company_reset']   ?? 'yearly')
        );
        $repo->save_rule($company);


        $license = new NumberingRuleDTO(
            entity_type: 'license',
            prefix: $out['numbering_license_prefix']  ?? ($current_all['numbering_license_prefix']  ?? 'LIC-{year}-'),
            suffix: $out['numbering_license_suffix']  ?? ($current_all['numbering_license_suffix']  ?? ''),
            padding: $out['numbering_license_padding'] ?? (int)($current_all['numbering_license_padding'] ?? 5),
            reset: $out['numbering_license_reset']   ?? ($current_all['numbering_license_reset']   ?? 'yearly')
        );
        $repo->save_rule($license);

        return $out;
    }
}
