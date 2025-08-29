<?php
declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Infrastructure\Security\Capability;

final class QuoteScreen
{
    public const PAGE_SLUG   = 'tmt-crm-quotes';
    public const ACTION_SAVE = 'tmt_crm_quote_save';

    public static function boot(): void
    {
        // Nếu bạn muốn hook riêng lúc load trang (screen options…)
        add_action('load-' . self::hook_suffix(), [self::class, 'on_load']);
        add_action('admin_post_' . self::ACTION_SAVE, [self::class, 'handle_save']);
    }

    // Lưu ý: hook_suffix() chỉ hoạt động khi trang đã được add_submenu_page
    private static function hook_suffix(): string
    {
        // Tránh lỗi: nếu chưa có hook thì trả về chuỗi bất kỳ
        return 'toplevel_page_tmt-crm_page_' . self::PAGE_SLUG;
    }

    public static function on_load(): void
    {
        // Enqueue assets riêng cho trang Quotes
        $page = $_GET['page'] ?? '';
        if ($page !== self::PAGE_SLUG) return;

        // // CSS nhẹ (tuỳ chọn)
        // wp_register_style('tmt-quote-css', plugins_url('assets/admin/css/quote.css', TMT_CRM_FILE), [], '1.0.0');
        // wp_enqueue_style('tmt-quote-css');

        // // JS tính tổng
        // wp_register_script('tmt-quote-js', plugins_url('assets/admin/js/quote-form.js', TMT_CRM_FILE), [], '1.0.0', true);
        // wp_enqueue_script('tmt-quote-js');
    }

    public static function dispatch(): void
    {
        if (! current_user_can(Capability::QUOTE_READ)) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'tmt-crm'), 403);
        }

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/quote-form.php';
        if (! file_exists($tpl)) {
            echo '<div class="wrap"><h1>Quotes</h1><p>Template chưa sẵn sàng.</p></div>';
            return;
        }
        require $tpl;
    }

    private static function guard_cap_nonce(string $nonce): void
    {
        if (! current_user_can(Capability::QUOTE_CREATE)) {
            wp_die(__('Bạn không có quyền thao tác mục này.', 'tmt-crm'), 403);
        }
        check_admin_referer($nonce);
    }

    public static function handle_save(): void
    {
        self::guard_cap_nonce('tmt_crm_quote_form');
        // TODO: gọi QuoteService để lưu (đã soạn trước đó).
        wp_safe_redirect(add_query_arg(['page'=>self::PAGE_SLUG,'saved'=>'1'], admin_url('admin.php')));
        exit;
    }
}
