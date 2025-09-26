<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Presentation\Assets;

/**
 * Gắn Select2 vào khu vực admin TMT-CRM
 */
final class Select2Assets
{
    public static function bootstrap(): void // (file chính) gọi hàm này khi plugin load admin
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets(): void
    {
        // Chỉ load ở màn hình của plugin CRM để nhẹ site
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'tmt-crm') === false) {
            return;
        }

        // Đăng ký vendor Select2 (tự bundle để không phụ thuộc WooCommerce)
        $base = TMT_CRM_URL; // hằng tự bạn đang có ở plugin chính (file chính)
        wp_register_style('tmt-select2', $base . 'assets/vendor/select2/select2.min.css', [], '4.1.0');
        wp_register_script('tmt-select2', $base . 'assets/vendor/select2/select2.full.min.js', ['jquery'], '4.1.0', true);

        // Script khởi tạo riêng cho plugin
        wp_register_script('tmt-crm-select2-init', $base . 'assets/js/select2-init.js', ['jquery', 'tmt-select2'], '0.1.0', true);

        // Localize dữ liệu chung cho Ajax
        wp_localize_script('tmt-crm-select2-init', 'TMTCRM_Select2', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tmt_crm_select2_nonce'),
            'i18n'     => [
                'placeholder_company'       => __('— Chọn công ty —', 'tmt-crm'),
                'placeholder_owner'         => __('— Chọn người phụ trách —', 'tmt-crm'),
                'placeholder_customer'      => __('— Chọn người liên hệ  —', 'tmt-crm'),
                'searching'                 => __('Đang tìm...', 'tmt-crm'),
                'no_results'                => __('Không có kết quả', 'tmt-crm'),
            ],
        ]);

        wp_enqueue_style('tmt-select2');
        wp_enqueue_script('tmt-select2');
        wp_enqueue_script('tmt-crm-select2-init');
        wp_enqueue_script(
            'tmt-quote-form',
            plugins_url('assets/admin/js/quote-form.js', TMT_CRM_FILE),
            ['jquery', 'tmt-select2'],
            '1.1.0',
            true
        );
    }
}
