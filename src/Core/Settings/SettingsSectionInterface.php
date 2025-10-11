<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Settings;

interface SettingsSectionInterface
{
    /** ID duy nhất của section (slug) */
    public function section_id(): string;

    /** Tiêu đề section hiển thị */
    public function section_title(): string;

    /**
     * Đăng ký các field (gọi add_settings_field)
     * @param string $page_slug Slug của trang settings (vd: tmt-crm-settings)
     * @param string $option_key Option name lưu trong wp_options
     */
    public function register_fields(string $page_slug, string $option_key): void;

    /**
     * Trả về mảng default cho section này: [key => default_value]
     */
    public function get_defaults(): array;

    /**
     * Sanitize dữ liệu post về của section này.
     * Nhận $input (chỉ phần tương ứng với section, hoặc cả mảng — tùy cách bạn trích).
     * Nên return mảng [key => value] hợp lệ cho section.
     */
    public function sanitize(array $input, array $current_all): array;
    /**
     * Capability để xem/chỉnh sửa section này.
     * Mặc định 'manage_options'.
     */
    public function capability(): string;

    /**
     * (Tuỳ chọn) HTML phần mô tả đầu section.
     * Trả '' nếu không dùng.
     */
    public function header_html(): string;
}
