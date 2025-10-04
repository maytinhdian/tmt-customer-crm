<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Log\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\SettingsSectionInterface;

/**
 * LoggingSettingsIntegration
 * - Tạo section "Logging" trong trang Settings (qua SettingsRegistry của bạn).
 * - Cung cấp 3 field: channel, min_level, keep_days.
 * - Có sanitize & defaults để Settings gộp vào option chung.
 */
final class LoggingSettingsIntegration implements SettingsSectionInterface
{
    /**
     * (Gợi ý) Gọi 1 lần ở bootstrap của LogModule
     * để đăng ký section vào Settings qua filter tmt_crm_settings_sections.
     */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', static function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    /** ID duy nhất của section (slug) */
    public function section_id(): string
    {
        return 'logging';
    }

    /** Tiêu đề section hiển thị */
    public function section_title(): string
    {
        return __('Logging', 'tmt-crm');
    }

    /**
     * Đăng ký các field (gọi add_settings_field)
     * @param string $page_slug  Slug trang settings (vd: tmt-crm-settings)
     * @param string $option_key Tên option lưu (vd: tmt_crm_settings)
     */
    public function register_fields(string $page_slug, string $option_key): void
    {
        // Lấy giá trị hiện tại (gộp default)
        $all   = get_option($option_key, []);
        $value = isset($all[$this->section_id()]) && is_array($all[$this->section_id()])
            ? array_merge($this->get_defaults(), $all[$this->section_id()])
            : $this->get_defaults();

        // 1) Field: channel (file|database|both)
        add_settings_field(
            "{$this->section_id()}_channel",
            __('Kênh ghi log', 'tmt-crm'),
            function () use ($option_key, $value) {
                $name  = $option_key . '[' . $this->section_id() . '][channel]';
                $id    = $this->section_id() . '_channel';
                $cur   = (string)($value['channel'] ?? 'file');
                $pairs = [
                    'file'     => __('File', 'tmt-crm'),
                    'database' => __('Database', 'tmt-crm'),
                    'both'     => __('Cả hai (File + DB)', 'tmt-crm'),
                ];
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach ($pairs as $key => $label) {
                    printf(
                        '<option value="%1$s"%2$s>%3$s</option>',
                        esc_attr($key),
                        selected($cur, $key, false),
                        esc_html($label)
                    );
                }
                echo '</select>';
                echo '<p class="description">' . esc_html__('Chọn nơi ghi log.', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // 2) Field: min_level
        add_settings_field(
            "{$this->section_id()}_min_level",
            __('Mức ghi log tối thiểu', 'tmt-crm'),
            function () use ($option_key, $value) {
                $name = $option_key . '[' . $this->section_id() . '][min_level]';
                $id   = $this->section_id() . '_min_level';
                $cur  = (string)($value['min_level'] ?? 'info');
                $levels = ['debug', 'info', 'warning', 'error', 'critical'];

                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach ($levels as $lv) {
                    printf(
                        '<option value="%1$s"%2$s>%1$s</option>',
                        esc_attr($lv),
                        selected($cur, $lv, false)
                    );
                }
                echo '</select>';
                echo '<p class="description">' . esc_html__('Chỉ ghi các log có mức độ >= giá trị này.', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );

        // 3) Field: keep_days (retention)
        add_settings_field(
            "{$this->section_id()}_keep_days",
            __('Số ngày giữ log', 'tmt-crm'),
            function () use ($option_key, $value) {
                $name = $option_key . '[' . $this->section_id() . '][keep_days]';
                $id   = $this->section_id() . '_keep_days';
                $cur  = (int)($value['keep_days'] ?? 30);
                echo '<input type="number" min="1" class="small-text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($cur) . '" />';
                echo '<p class="description">' . esc_html__('Sau số ngày này log cũ sẽ được xoá (file + database).', 'tmt-crm') . '</p>';
            },
            $page_slug,
            $this->section_id()
        );
    }

    /**
     * Trả về mảng default cho section này
     * @return array{channel:string,min_level:string,keep_days:int}
     */
    public function get_defaults(): array
    {
        return [
            'channel'   => 'file',   // file|database|both
            'min_level' => 'info',   // debug|info|warning|error|critical
            'keep_days' => 30,       // retention days
        ];
    }

    /**
     * Sanitize dữ liệu post về của section này.
     * @param array $input       Phần dữ liệu của section logging (ví dụ $_POST['tmt_crm_settings']['logging'])
     * @param array $current_all Toàn bộ option hiện tại (để tham chiếu nếu cần)
     * @return array [key => value] hợp lệ cho section "logging"
     */
    // public function sanitize(array $input, array $current_all): array
    // {
    //     $defaults = $this->get_defaults();
    //     $out      = [];

    //     // channel
    //     $allowed_channels = ['file', 'database', 'both'];
    //     $in_channel = isset($input['channel']) ? (string)$input['channel'] : $defaults['channel'];
    //     $out['channel'] = in_array($in_channel, $allowed_channels, true) ? $in_channel : $defaults['channel'];

    //     // min_level
    //     $allowed_levels = ['debug', 'info', 'warning', 'error', 'critical'];
    //     $in_level = isset($input['min_level']) ? (string)$input['min_level'] : $defaults['min_level'];
    //     $out['min_level'] = in_array($in_level, $allowed_levels, true) ? $in_level : $defaults['min_level'];

    //     // keep_days
    //     $in_days = isset($input['keep_days']) ? (int)$input['keep_days'] : $defaults['keep_days'];
    //     $out['keep_days'] = ($in_days > 0) ? $in_days : $defaults['keep_days'];

    //     return $out;
    // }
    public function sanitize(array $input, array $current_all): array
    {
        $defaults = $this->get_defaults();

        // Tương thích: nếu $input là toàn bộ option, trích phần của section này
        $sid = $this->section_id(); // 'logging'
        if (isset($input[$sid]) && is_array($input[$sid])) {
            $input = $input[$sid];
        }

        // channel
        $allowed_channels = ['file', 'database', 'both'];
        $in_channel = isset($input['channel']) ? (string)$input['channel'] : $defaults['channel'];
        $channel = in_array($in_channel, $allowed_channels, true) ? $in_channel : $defaults['channel'];

        // min_level
        $allowed_levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $in_level = isset($input['min_level']) ? (string)$input['min_level'] : $defaults['min_level'];
        $min_level = in_array($in_level, $allowed_levels, true) ? $in_level : $defaults['min_level'];

        // keep_days
        $in_days = isset($input['keep_days']) ? (int)$input['keep_days'] : $defaults['keep_days'];
        $keep_days = ($in_days > 0) ? $in_days : $defaults['keep_days'];

        // QUAN TRỌNG: trả về mảng THEO SECTION
        return [
            $sid => [
                'channel'   => $channel,
                'min_level' => $min_level,
                'keep_days' => $keep_days,
            ],
        ];
    }
}
