<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\ListTable\CompanyListTable;
use TMT\CRM\Application\DTO\CompanyDTO;

defined('ABSPATH') || exit;

/**
 * Màn hình quản trị: Công ty (Companies)
 */
final class CompanyScreen
{
    /** Slug trang companies trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-companies';

    /** Tên action cho admin-post */
    public const ACTION_SAVE   = 'tmt_crm_company_save';
    public const ACTION_DELETE = 'tmt_crm_company_delete';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_companies_per_page';

    /** Đăng ký các handler admin_post (submit form) */
    public static function boot(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_DELETE, [self::class, 'handle_delete']);

        add_filter('set-screen-option', [CompanyScreen::class, 'save_screen_option'], 10, 3);
        add_filter('set-screen-option', function ($status, $option, $value) {
            return ($option === 'tmt_crm_customers_per_page') ? (int)$value : $status;
        }, 10, 3);
    }

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
        // ⚠️ Đổi đúng ID theo log current_screen của bạn
        if (
            $screen->id === 'crm_page_tmt-crm-companies'
            || $screen->id === 'crm_page_tmt-crm-companies'
        ) {
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

    /** LIST VIEW: dùng CompanyListTable + bulk delete */
    public static function render_list(): void
    {
        $table = new CompanyListTable();

        // Bulk delete (nếu list-table submit)
        if (current_user_can(Capability::COMPANY_DELETE) && $table->current_action() === 'bulk-delete') {
            check_admin_referer('bulk-companies');

            // Giống CustomerListTable: CompanyListTable nên có method này.
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
                            error_log('[tmt-crm] bulk delete companies failed id=' . $id . ' msg=' . $e->getMessage());
                        }
                    }
                }
                wp_safe_redirect(self::url(['deleted' => count($ids)]));
                exit;
            }
        }

        // Nạp dữ liệu + phân trang
        $table->prepare_items();

        // Notices
        if (isset($_GET['created'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã tạo công ty.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã cập nhật công ty.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Đã xoá %s công ty.', 'tmt-crm'), (int) $_GET['deleted']) . '</p></div>';
        }
        if (isset($_GET['error']) && !empty($_GET['msg'])) {
            echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash((string) $_GET['msg'])) . '</p></div>';
        }

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
        $svc = Container::get('company-service');
        $company = null;

        if ($id > 0) {
            /** @var CompanyDTO|null $company */
            $company = $svc->find_by_id($id);
            if (!$company) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Không tìm thấy công ty.', 'tmt-crm') . '</p></div>';
                return;
            }
        }

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/company-form.php';
        if (file_exists($tpl)) {
            /** @var CompanyDTO|null $company */
            /** @var string $nonce_name */
            include $tpl;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Template company-form.php không tồn tại.', 'tmt-crm') . '</p></div>';
        }
    }

    /** Handler: Save (Create/Update) */
    public static function handle_save(): void
    {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh: tạo hay cập nhật
        if ($id > 0) {
            self::ensure_capability(Capability::COMPANY_UPDATE, __('Bạn không có quyền sửa công ty.', 'tmt-crm'));
        } else {
            self::ensure_capability(Capability::COMPANY_CREATE, __('Bạn không có quyền tạo công ty.', 'tmt-crm'));
        }

        // Kiểm nonce
        $nonce_name = $id > 0 ? 'tmt_crm_company_update_' . $id : 'tmt_crm_company_create';
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], $nonce_name)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Sanitize input
        $owner_id = isset($_POST['owner_id'])
            ? absint(wp_unslash($_POST['owner_id']))
            : 0;
        $owner_id = $owner_id > 0 ? $owner_id : null;

        // Nếu "representer" là tên người đại diện (text)
        $representer = isset($_POST['representer'])
            ? sanitize_text_field(wp_unslash($_POST['representer']))
            : '';
        $representer = ($representer !== '') ? $representer : null;
        // Sanitize input
        $data = [
            'name'     => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'tax_code' => sanitize_text_field(wp_unslash($_POST['tax_code'] ?? '')),
            'address'  => sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')),
            'phone'    => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'email'    => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'website'  => esc_url_raw(wp_unslash($_POST['website'] ?? '')),
            'note'     => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
            'owner_id'    => $owner_id,
            'representer' => $representer,
        ];

        $svc = Container::get('company-service');

        try {
            if ($id > 0) {
                $svc->update($id, $data);
                self::redirect(self::url(['updated' => 1]));
            } else {
                $new_id = $svc->create($data);
                self::redirect(self::url(['created' => 1]));
            }
        } catch (\Throwable $e) {
            self::redirect(self::url([
                'error' => 1,
                'msg'   => rawurlencode($e->getMessage()),
            ]));
        }
    }

    /** Handler: Delete (single) */
    public static function handle_delete(): void
    {
        self::ensure_capability(Capability::COMPANY_DELETE, __('Bạn không có quyền xoá công ty.', 'tmt-crm'));

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'tmt_crm_company_delete_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('company-service');

        try {
            $svc->delete($id);
            self::redirect(self::url(['deleted' => 1]));
        } catch (\Throwable $e) {
            self::redirect(self::url([
                'error' => 1,
                'msg'   => rawurlencode($e->getMessage()),
            ]));
        }
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
