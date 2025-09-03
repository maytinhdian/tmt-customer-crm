<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Screen;

use TMT\CRM\Application\DTO\CompanyDTO;
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
        $table  = new CompanyContactsListTable(0); // company_id set khi render, ở đây chỉ lấy cột
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
        if ($screen->id === 'crm_page_' . self::PAGE_SLUG) {
            // ví dụ ẩn cột 'period'
            $hidden = array_unique(array_merge($hidden, ['period']));
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

    /** LIST VIEW: WP_List_Table + form Thêm liên hệ (template ngoài src để tránh PSR-4) */
    public static function render_manage(int $company_id): void
    {

        // Lấy dữ liệu công ty để render tiêu đề
        $svc     = Container::get('company-service');
        $companyDTO = $svc->find_by_id($company_id); // CompanyDTO|array tuỳ Service
        if (!$companyDTO) {
            wp_die(__('Không tìm thấy công ty', 'tmt-crm'));
        }
        $company = method_exists($companyDTO, 'to_array')
            ? $companyDTO->to_array(true)
            : ['id' => (int)($companyDTO->id ?? 0), 'name' => (string)($companyDTO->name ?? '')];
        $table = new CompanyContactsListTable($company_id);
        $table->prepare_items();

        // ✅ Gọi template chính bằng View::render_admin_module
        View::render_admin_module('company', 'contacts-manage.php', [
            'company'    => $company,
            'company_id' => $company_id,
            'table'      => $table,
        ]);
    }

    /** Helper build URL (giống CompanyScreen::url) */
    private static function url(array $args = []): string
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
