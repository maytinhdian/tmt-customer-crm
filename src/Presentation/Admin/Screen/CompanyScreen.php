<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container;
use TMT\CRM\Presentation\Support\View;
use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\ListTable\CompanyListTable;
use TMT\CRM\Presentation\Admin\Support\AdminNoticeService;
use TMT\CRM\Presentation\Admin\Screen\CompanyNotesFilesScreen;

defined('ABSPATH') || exit;

final class CompanyScreen
{
    private static ?string $hook_suffix = null;

    /** Slug trang companies trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-companies';

    /** Tên action cho admin-post (giữ để form cũ không phải sửa) */
    public const ACTION_SAVE   = 'tmt_crm_company_save';
    public const ACTION_DELETE = 'tmt_crm_company_delete';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_companies_per_page';

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

    /** (ĐÃ GỠ) admin_post handlers đã tách sang CompanyController */

    /** Được gọi khi load trang Companies để in Screen Options (per-page) */
    public static function on_load_companies(): void
    {
        if (!current_user_can(Capability::COMPANY_CREATE)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số công ty mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);

        // ✅ Báo cho Screen Options biết danh sách cột (để hiện checkbox Columns)
        $screen = get_current_screen();
        $table  = new CompanyListTable();
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
        if ($screen->id === self::$hook_suffix) {
            $hidden = array_unique(array_merge($hidden, ['id', 'owner', 'representer', 'created_at']));
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
            return (int) $value;
        }
        return $status;
    }

    /** Router view theo tham số ?action=... */
    public static function dispatch(): void
    {
        self::ensure_capability(Capability::COMPANY_CREATE, __('Bạn không có quyền truy cập danh sách công ty.', 'tmt-crm'));

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : 'list';

        if ($action === 'add') {
            self::ensure_capability(Capability::COMPANY_CREATE, __('Bạn không có quyền tạo công ty.', 'tmt-crm'));
            self::render_form();
            return;
        }

        if ($action === 'edit') {
            self::ensure_capability(Capability::COMPANY_UPDATE, __('Bạn không có quyền sửa công ty.', 'tmt-crm'));
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            self::render_form($id);
            return;
        }

        self::render_list();
    }

    /** LIST VIEW: dùng CompanyListTable + bulk delete (giữ nguyên flow hiện tại) */
    public static function render_list(): void
    {
        $table = new CompanyListTable();

        // Bulk delete (nếu list-table submit)
        if (current_user_can(Capability::COMPANY_DELETE) && $table->current_action() === 'bulk-delete') {
            check_admin_referer('bulk-companies');

            $ids = method_exists($table, 'get_selected_ids_for_bulk_delete')
                ? $table->get_selected_ids_for_bulk_delete()
                : array_map('absint', (array)($_POST['ids'] ?? []));

            if (!empty($ids)) {
                $svc = Container::get('company-service');
                foreach ($ids as $id) {
                    try {
                        $svc->delete((int)$id);
                    } catch (\Throwable $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            AdminNoticeService::error_for_screen(self::$hook_suffix, $e->getMessage());
                            error_log('[tmt-crm] bulk delete companies failed id=' . $id . ' msg=' . $e->getMessage());
                            self::redirect(self::url(['error' => 1]));
                        }
                    }
                }
                AdminNoticeService::success_for_screen(
                    self::$hook_suffix,
                    sprintf(__('Đã xóa %d công ty.', 'tmt-crm'), count($ids))
                );
                wp_safe_redirect(self::url(['deleted' => count($ids)]));
                exit;
            }
        }

        // Nạp dữ liệu + phân trang
        $table->prepare_items();

        $add_url = self::url(['action' => 'add']); ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách công ty', 'tmt-crm'); ?></h1>
            <?php if (current_user_can(Capability::COMPANY_CREATE)) : ?>
                <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php esc_html_e('Thêm mới', 'tmt-crm'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end" />

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>" />
                <?php
                $table->search_box(__('Tìm kiếm công ty', 'tmt-crm'), 'company');
                $table->display();
                wp_nonce_field('bulk-companies');
                ?>
            </form>
        </div>
<?php
    }

    /** FORM VIEW: Add/Edit */
    public static function render_form(int $id = 0): void
    {
        $svc     = Container::get('company-service');
        $company = null;

        if ($id > 0) {
            /** @var CompanyDTO|null $company */
            $company = $svc->find_by_id($id);
            if (!$company) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Không tìm thấy công ty.', 'tmt-crm') . '</p></div>';
                return;
            }
        }

        View::render_admin_module('company', 'company-form', [
            'company'    => $company,
            'nonce_name' => $id > 0 ? ('tmt_crm_company_update_' . $id) : 'tmt_crm_company_create',
        ]);
    }

    /* ===================== Helpers ===================== */

    /** Build URL admin.php?page=tmt-crm-companies + $args */
    private static function url(array $args = []): string
    {
        $base = admin_url('admin.php');
        $args = array_merge(['page' => self::PAGE_SLUG], $args);
        return add_query_arg($args, $base);
    }

    /** Kiểm tra quyền, nếu không đủ -> die với thông báo */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    /** Redirect & exit */
    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
