<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Screen;


use TMT\CRM\Shared\Container;

use TMT\CRM\Presentation\Support\View;
use TMT\CRM\Infrastructure\Security\Capability;

use TMT\CRM\Presentation\Admin\ListTable\CompanyContactsListTable;

defined('ABSPATH') || exit;

/**
 * Màn hình quản trị: Quản lý liên hệ theo Công ty
 * - Theo form CompanyScreen (dispatch, on_load, Screen Options, url(), ensure_capability, redirect)
 */
final class CompanyContactsScreen
{
    private static ?string $hook_suffix = null;

    /** Slug trang */
    public const PAGE_SLUG = 'tmt-crm-company-contacts';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_company_contacts_per_page';

    /**
     * Đăng ký handler chung (screen-option ...)
     * Gọi 1 lần ở bootstrap (file chính)
     */
    public static function boot(): void
    {
        // Lưu Screen Options (per-page)
        add_filter('set-screen-option', [self::class, 'save_screen_option'], 10, 3);
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
            error_log('Company Contact Screen $hook_suffix: ' . $hook_suffix);
        }

        // fallback nếu chưa được set (ít xảy ra)
        return 'crm_page_' . self::PAGE_SLUG;
    }
    /** In Screen Options (per-page) & cấu hình cột */
    public static function on_load_contacts(): void
    {
        if (!current_user_can(Capability::COMPANY_READ)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số liên hệ mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);

        // Khai báo danh sách cột cho Screen Options → Columns
        $screen = get_current_screen();

        // ✅ Cách 1: tạo instance "nhẹ" chỉ để lấy cột (không lệ thuộc dữ liệu)
        $table  = new CompanyContactsListTable([], 0, 20, 0);

        // Khai báo danh sách cột cho Screen Options → Columns
        $screen = get_current_screen();
        add_filter("manage_{$screen->id}_columns", static function () use ($table) {
            $cols = $table->get_columns();
            unset($cols['cb']); // không cho bật/tắt cột checkbox
            return $cols;
        });
        // Ẩn cột mặc định (điều chỉnh theo nhu cầu)
        add_filter('default_hidden_columns', [self::class, 'default_hidden_columns'], 10, 2);
    }

    /** Ẩn cột mặc định cho screen này */
    public static function default_hidden_columns(array $hidden, \WP_Screen $screen): array
    {
        // Nhớ kiểm tra $screen->id thực tế (log current_screen) để khớp
        if ($screen->id === 'crm_page_tmt-crm-company-contacts') {
            // ví dụ ẩn cột 'period'
            $hidden = array_unique(array_merge($hidden, ['owner_contact', 'id']));
        }
        return $hidden;
    }

    /**
     * Lưu Screen Options per-page
     * @param mixed  $status
     * @param string $option
     * @param mixed  $value
     * @return mixed
     */
    public static function save_screen_option($status, $option, $value)
    {
        if ($option === self::OPTION_PER_PAGE) {
            return (int) $value;
        }
        return $status;
    }

    /** Router view; yêu cầu có company_id */
    public static function dispatch(): void
    {
        self::ensure_capability(
            Capability::COMPANY_READ,
            __('Bạn không có quyền truy cập trang liên hệ công ty.', 'tmt-crm')
        );

        $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        if ($company_id <= 0) {
            wp_die(__('Thiếu company_id', 'tmt-crm'));
        }

        // 1 view: danh sách liên hệ + form gán
        self::render_manage($company_id);
    }


    public static function render_manage(int $company_id): void
    {
        /** @var \TMT\CRM\Application\Services\CompanyContactQueryService $svc */
        $svc = \TMT\CRM\Shared\Container::get('company-contact-query-service');

        // ====== Parse request (list + filter + sort) ======
        $user_per_page = (int) get_user_option(self::OPTION_PER_PAGE);
        $per_page      = $user_per_page > 0 ? $user_per_page : 20;
        $current_page  = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

        $filters = [
            'active_only' => isset($_GET['active_only']) ? (bool) (int) $_GET['active_only'] : true,
            'role'        => sanitize_text_field($_GET['role'] ?? ''),
        ];

        $allowed_orderby = ['id', 'role', 'position', 'start_date', 'end_date', 'is_primary'];
        $orderby = sanitize_key($_GET['orderby'] ?? '');
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = ''; // để service tự mặc định
        }
        $sort = [
            'by'  => $orderby,
            'dir' => strtolower(sanitize_text_field($_GET['order'] ?? '')) === 'asc' ? 'asc' : 'desc',
        ];

        // ====== Data chung cho bảng ======
        $items        = $svc->find_paged_view_by_company($company_id, $current_page, $per_page, $filters, $sort);
        $total_items  = $svc->count_view_by_company($company_id, $filters);
        $company_name = $svc->get_company_name($company_id);

        $table = new \TMT\CRM\Presentation\Admin\ListTable\CompanyContactsListTable(
            $items,
            $total_items,
            $per_page,
            $company_id
        );
        $table->prepare_items();

        // ====== Xác định mode hiển thị (list | edit) ======
        // DÙNG 'view' cho UI để không đụng 'action' của admin-post.php
        $view       = sanitize_key($_GET['view'] ?? 'list');
        $is_editing = ($view === 'edit');
        $contact_id = $is_editing ? absint($_GET['contact_id'] ?? 0) : 0;

        // ====== Nạp data cho form Edit (nếu có) ======
        $edit_contact = null;
        if ($is_editing && $contact_id > 0) {
            /** @var \TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface $repo */
            $repo = \TMT\CRM\Shared\Container::get('company-contact-repo');
            if ($repo && method_exists($repo, 'find_by_id')) {
                $edit_contact = $repo->find_by_id($contact_id);
                // Trước khi gọi module
                error_log('[CRM] Screen→module edit_contact: ' . (is_object($edit_contact) ? get_class($edit_contact) : gettype($edit_contact)));
                // Bảo toàn dữ liệu: bắt buộc thuộc đúng company
                if (
                    !$edit_contact instanceof \TMT\CRM\Application\DTO\CompanyContactDTO ||
                    (int)$edit_contact->company_id !== (int)$company_id
                ) {
                    // Ghi log để bạn thấy đang nhận kiểu gì
                    error_log('[CRM] edit_contact type: ' . (is_object($edit_contact) ? get_class($edit_contact) : gettype($edit_contact)));
                    $is_editing   = false;
                    $edit_contact = null;
                }
            } else {
                $is_editing = false;
            }
        }

        // ====== Render duy nhất qua View:: ======
        $module = 'company';
        $file   = 'contacts-list';
        $vars   = [
            'company_id'   => (int) $company_id,
            'company_name' => $company_name,
            'total_items'  => $total_items,
            'table'        => $table,

            // Luôn truyền để template chủ động (đỡ if/else ở Controller)
            'editing'      => (bool) $is_editing,
            'contact_id'   => (int) $contact_id,
            'edit_contact' => $edit_contact, // DTO | null
            // Nếu form cần danh sách role hiển thị:
            // 'roles'     => \TMT\CRM\Domain\ValueObject\CompanyContactRole::labels(),
        ];

        if (View::exists_admin($module . '/' . $file)) {
            View::render_admin_module($module, $file, $vars);
            return;
        }
    }




    /** Lấy state hiện tại để giữ phân trang, sort, filter khi điều hướng */
    public static function current_state(): array
    {
        $keep = ['paged', 'orderby', 'order', 'role', 'active_only', 's', 'per_page'];
        $state = [];

        foreach ($keep as $k) {
            if (!isset($_GET[$k])) {
                continue;
            }
            // active_only có thể là boolean/string; còn lại xử lý về string an toàn
            if ($k === 'active_only') {
                $state[$k] = (int) !! $_GET[$k];
                continue;
            }
            $val = wp_unslash($_GET[$k]);
            $state[$k] = is_array($val)
                ? array_map('sanitize_text_field', $val)
                : sanitize_text_field((string) $val);
        }

        return $state;
    }

    /** URL mở form Sửa liên hệ (giữ tab + state hiện tại) */
    public static function edit_url(int $company_id, int $customer_id, int $contact_id, array $state = []): string
    {
        return self::url(array_merge([
            'action'     => 'edit',
            'company_id' => $company_id,
            'customer_id' => $customer_id,
            'contact_id' => $contact_id,
            'view'       => 'edit',
        ], $state));
    }

    /** URL quay lại danh sách contacts (đã giữ state) */
    public static function back_url(int $company_id, array $extra = []): string
    {
        return self::url(array_merge([
            'company_id' => $company_id,
        ], $extra, self::current_state()));
    }

    /** Helper build URL (giống CompanyScreen::url) */
    public static function url(array $args = []): string
    {
        $base = ['page' => self::PAGE_SLUG];
        $args = array_merge($base, $args);
        return add_query_arg($args, admin_url('admin.php'));
    }

    /** Kiểm tra quyền, không đủ → die (đúng “dạng” CompanyScreen) */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    /** Redirect & exit (đồng bộ CompanyScreen) */
    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
