<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Shared\Presentation\AdminNoticeService;
use TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteQueryRepositoryInterface;
use TMT\CRM\Modules\Quotation\Presentation\Admin\ListTable\QuoteListTable;

final class QuoteScreen
{
    // private QuoteQueryRepositoryInterface $query_repo;

    public const PAGE_SLUG = 'tmt-crm-quotes';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_quotes_per_page';

    /** hook_suffix để scope AdminNotice/Screen Options đúng vào màn này */
    private static string $hook_suffix = '';

    public static function set_hook_suffix(string $hook): void
    {
        self::$hook_suffix = $hook;
    }

    public static function hook_suffix(): string
    {
        return self::$hook_suffix ?: 'crm_page_' . self::PAGE_SLUG;
    }

    /** Được gọi khi load trang Quotes để in Screen Options (per-page) */
    public static function on_load_quotes(): void
    {
        if (!current_user_can(Capability::QUOTE_READ)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số báo giá mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);

        $query_repo = Container::get('quote-query-repo');
        // ✅ Báo cho Screen Options biết danh sách cột (để hiện checkbox Columns)
        $screen = get_current_screen();
        $table  = new QuoteListTable($query_repo);
        add_filter("manage_{$screen->id}_columns", static function () use ($table) {
            $cols = $table->get_columns();
            unset($cols['cb']); // không cho bật/tắt cột checkbox
            return $cols;
        });

        // ✅ Ẩn/hiện cột theo mặc định cho screen này
        add_filter('default_hidden_columns', [self::class, 'default_hidden_columns'], 10, 2);
    }

    public static function default_hidden_columns(array $hidden, \WP_Screen $screen): array
    {
        // ⚠️ Đổi đúng ID theo log current_screen của bạn
        if (
            $screen->id === self::$hook_suffix
        ) {
            $hidden = array_unique(array_merge($hidden, ['id']));
        }
        return $hidden;
    }
    /**
     * Lưu giá trị Screen Options per-page
     * @param mixed  $status
     * @param string $option
     * @param mixed  $value
     * @return mixed
     */
    public static function save_screen_option($status, $option, $value)
    {
        if ($option === self::OPTION_PER_PAGE) {
            $v = max(1, min(200, (int)$value)); // ép kiểu + ràng giới hạn an toàn
            return $v; // PHẢI trả về giá trị muốn lưu
        }
        return $status; // giữ nguyên cho option khác
    }

    public static function dispatch(): void
    {
        if (! current_user_can(Capability::QUOTE_READ)) {
            wp_die(__('Bạn không có quyền truy cập.', 'tmt-crm'), 403);
        }

        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        // $base   = trailingslashit(TMT_CRM_PATH) . 'templates/admin/quotes/';

        if ($action === 'new' || $action === 'edit') {
            View::render_admin_module('quotes', 'form', []);
            // require $base . 'form.php';
        } else {
             View::render_admin_module('quotes', 'list', []);
            // require $base . 'list.php';
        }
    }


    /** Form tạo/sửa (nếu bạn đang dùng 1 view riêng) */
    public static function render_form(): void
    {
        // Lấy id nếu có
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        // Tự lấy dữ liệu quote để đổ form (tuỳ service hiện hữu của bạn)
        $quote = null;
        if ($id > 0) {
            /** @var \TMT\CRM\Shared\Container $c */
            $svc = \TMT\CRM\Shared\Container::get('quote-service');
            $quote = $svc->find($id); // giả định có hàm find(); nếu không có, thay bằng repo/get hiện có
        }

        View::render_admin_module('quotes', 'form', [
            'quote' => $quote,
        ]);
    }
}
