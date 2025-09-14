<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Presentation\Admin\Screen;

use TMT\CRM\Modules\Quotation\Application\DTO\{QuoteDTO, QuoteItemDTO};

use TMT\CRM\Shared\Container;

use TMT\CRM\Infrastructure\Security\Capability;

final class QuoteScreen
{
    private static ?string $hook_suffix = null;

    public const PAGE_SLUG   = 'tmt-crm-quotes';
    public const ACTION_SAVE  = 'tmt_crm_quote_save';
    public const ACTION_DELETE = 'tmt_crm_quote_delete';

    public static function boot(): void
    {
        // Nếu bạn muốn hook riêng lúc load trang (screen options…)
        add_action('load-' . self::hook_suffix(), [self::class, 'on_load_quotes']);
        add_action('admin_post_' . self::ACTION_SAVE, [self::class, 'handle_save']);
    }

    /** Menu.php sẽ gọi hàm này sau khi đăng ký submenu */
    public static function set_hook_suffix(string $hook): void
    {
        self::$hook_suffix = $hook;
    }

    /** Trả về hook_suffix để AdminNoticeService scope đúng screen */
    public static function hook_suffix(): string
    {
        if (!empty(self::$hook_suffix)) {
            return self::$hook_suffix;
        }

        // fallback nếu chưa được set (ít xảy ra)
        return 'crm_page_' . self::PAGE_SLUG;
    }

    public static function on_load_quotes(): void
    {
        // Enqueue assets riêng cho trang Quotes
        $page = $_GET['page'] ?? '';
        if ($page !== self::PAGE_SLUG) return;

        // Screen option: số dòng mỗi trang
        add_filter('set-screen-option', [self::class, 'set_screen_option'], 10, 3);
        add_screen_option('per_page', [
            'label'   => __('Báo giá mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => 'tmt_crm_quotes_per_page',
        ]);


        add_filter('tmt_crm/quote_list/customer_display', function ($display, $row) {
            // Ví dụ đọc từ bảng customers của bạn:
            // return get_customer_name((int)$row['customer_id']) ?: $display;
            return $display; // giữ mặc định "KH #ID" nếu chưa có
        }, 10, 2);


        // CSS nhẹ (tuỳ chọn)
        wp_register_style('tmt-crm-ui', plugins_url('assets/admin/css/tmt-crm-ui.css', TMT_CRM_FILE), [], '1.0.0');
        wp_enqueue_style('tmt-crm-ui');

        // JS tính tổng
        wp_register_script('tmt-crm-quote-ui', plugins_url('assets/admin/js/quote-ui.js', TMT_CRM_FILE), [], '1.0.0', true);
        wp_enqueue_script('tmt-crm-quote-ui');
    }

    public static function dispatch(): void
    {
        if (! current_user_can(Capability::QUOTE_READ)) {
            wp_die(__('Bạn không có quyền truy cập.', 'tmt-crm'), 403);
        }

        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        $base   = trailingslashit(TMT_CRM_PATH) . 'templates/admin/quotes/';

        if ($action === 'new' || $action === 'edit') {
            require $base . 'form.php';
        } else {
            require $base . 'list.php';
        }
    }


    public static function handle_save(): void
    {
        self::guard_cap_nonce('tmt_crm_quote_form');

        /** @var QuoteService $svc */
        $svc = Container::get('quote-service');

        $dto = new QuoteDTO();
        $dto->customer_id = (int)($_POST['customer_id'] ?? 0);
        $dto->company_id  = ($_POST['company_id'] ?? '') !== '' ? (int)$_POST['company_id'] : null;
        $dto->owner_id    = (int)($_POST['owner_id'] ?? 0);
        $dto->currency    = sanitize_text_field($_POST['currency'] ?? 'VND');
        $dto->note        = sanitize_textarea_field($_POST['note'] ?? '');
        $dto->expires_at  = !empty($_POST['expires_at'])
            ? new \DateTimeImmutable(sanitize_text_field($_POST['expires_at']))
            : null;

        $dto->items = [];
        $skus = $_POST['sku'] ?? [];
        foreach ($skus as $i => $sku) {
            $it = new QuoteItemDTO();
            $it->sku        = sanitize_text_field($sku);
            $it->name       = sanitize_text_field($_POST['name'][$i] ?? '');
            $it->qty        = (float)($_POST['qty'][$i] ?? 0);
            $it->unit_price = (float)($_POST['unit_price'][$i] ?? 0);
            $it->discount   = (float)($_POST['discount'][$i] ?? 0);
            $it->tax_rate   = (float)($_POST['tax_rate'][$i] ?? 0);
            $dto->items[]   = $it;
        }

        $svc->create_draft($dto);

        wp_safe_redirect(add_query_arg(['page' => self::PAGE_SLUG, 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    /**************************************************************
     * Helper                                                     *
     **************************************************************/
    private static function guard_cap_nonce(string $nonce): void
    {
        if (! current_user_can(Capability::QUOTE_CREATE)) {
            wp_die(__('Bạn không có quyền thao tác mục này.', 'tmt-crm'), 403);
        }
        check_admin_referer($nonce);
    }
}
