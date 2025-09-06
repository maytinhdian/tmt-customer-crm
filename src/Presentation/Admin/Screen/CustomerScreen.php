<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\Support\AdminNoticeService;
use TMT\CRM\Presentation\Admin\ListTable\CustomerListTable;
use TMT\CRM\Application\DTO\CustomerDTO;

defined('ABSPATH') || exit;

/**
 * Màn hình quản trị: Khách hàng (Customers)
 */
final class CustomerScreen
{
    private static ?string $hook_suffix = null;

    /** Slug trang customers trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-customers';

    /** Tên action cho admin-post */
    public const ACTION_SAVE   = 'tmt_crm_customer_save';
    public const ACTION_DELETE = 'tmt_crm_customer_delete';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_customers_per_page';

    /** Đăng ký các handler admin_post (submit form) */
    public static function boot(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_DELETE, [self::class, 'handle_delete']);
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
    public static function render(): void
    {
        $table = new CustomerListTable();
        $table->prepare_items();
?>
        <div class="wrap">
            <h1><?php esc_html_e('Khách hàng', 'tmt-crm'); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="tmt-crm-customers" />
                <?php $table->display(); ?>
            </form>
        </div>
    <?php
    }


    /** Được gọi khi load trang Customers để in Screen Options (per-page) */
    public static function on_load_customers(): void
    {
        if (!current_user_can(Capability::CUSTOMER_READ)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số khách hàng mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);

        // ✅ Báo cho Screen Options biết danh sách cột (để hiện checkbox Columns)
        $screen = get_current_screen();
        $table  = new CustomerListTable();
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
            $screen->id === 'crm_page_tmt-crm-customers'
            || $screen->id === 'crm_page_tmt-crm-customers'
        ) {
            $hidden = array_unique(array_merge($hidden, ['id', 'owner_id']));
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

    /** Router view theo tham số ?action=... */
    public static function dispatch(): void
    {
        self::ensure_capability(Capability::CUSTOMER_READ, __('Bạn không có quyền truy cập danh sách khách hàng.', 'tmt-crm'));

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : 'list';

        if ($action === 'add') {
            self::ensure_capability(Capability::CUSTOMER_CREATE, __('Bạn không có quyền tạo khách hàng.', 'tmt-crm'));
            self::render_form();
            return;
        }

        if ($action === 'edit') {
            self::ensure_capability(Capability::CUSTOMER_UPDATE_ANY, __('Bạn không có quyền sửa khách hàng.', 'tmt-crm'));
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            self::render_form($id);
            return;
        }

        self::render_list();
    }

    /** LIST VIEW: dùng CustomerListTable + bulk delete */
    public static function render_list(): void
    {
        $table = new CustomerListTable();

        // Bulk delete (nếu list-table submit)
        if (current_user_can(Capability::CUSTOMER_DELETE) && $table->current_action() === 'bulk-delete') {
            check_admin_referer('bulk-customers');

            $ids = $table->get_selected_ids_for_bulk_delete();
            if (!empty($ids)) {
                $svc = Container::get('customer-service');
                foreach ($ids as $id) {
                    try {
                        $svc->delete($id);
                    } catch (\Throwable $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('[tmt-crm] bulk delete failed id=' . $id . ' msg=' . $e->getMessage());
                        }
                    }
                }
                wp_safe_redirect(self::url(['deleted' => count($ids)]));
                exit;
            }
        }

        // Nạp dữ liệu + phân trang
        $table->prepare_items();

        // // Notices
        // if (isset($_GET['created'])) {
        //     echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã tạo khách hàng.', 'tmt-crm') . '</p></div>';
        // }
        // if (isset($_GET['updated'])) {
        //     echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã cập nhật khách hàng.', 'tmt-crm') . '</p></div>';
        // }
        // if (isset($_GET['deleted'])) {
        //     echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Đã xoá %s khách hàng.', 'tmt-crm'), (int) $_GET['deleted']) . '</p></div>';
        // }
        // if (isset($_GET['error']) && !empty($_GET['msg'])) {
        //     echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash((string) $_GET['msg'])) . '</p></div>';
        // }

        $add_url = self::url(['action' => 'add']); ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách khách hàng', 'tmt-crm'); ?></h1>
            <?php if (current_user_can(Capability::CUSTOMER_CREATE)) : ?>
                <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php esc_html_e('Thêm mới', 'tmt-crm'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end" />

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>" />
                <?php
                $table->search_box(__('Tìm kiếm khách hàng', 'tmt-crm'), 'customer');
                $table->display();
                wp_nonce_field('bulk-customers');
                ?>
            </form>
        </div>
<?php
    }

    /** FORM VIEW: Add/Edit */
    public static function render_form(int $id = 0): void
    {
        $svc = Container::get('customer-service');
        $customer = null;

        /** @var \TMT\CRM\Domain\Repositories\UserRepositoryInterface $user_repo */
        $user_repo = Container::get('user-repo');

        // ✅ Giá trị mặc định
        $owner_id_selected = (int)($customer->owner_id ?? get_current_user_id());

        if ($id > 0) {
            /** @var CustomerDTO|null $customer */
            $customer = $svc->get_by_id($id);
            if (!$customer) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Không tìm thấy khách hàng.', 'tmt-crm') . '</p></div>';
                return;
            }
        }

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/customer-form.php';
        if (file_exists($tpl)) {
            /** @var CustomerDTO|null $customer */
            /** @var string $nonce_name */
            /** @var array<int,string> $owner_choices */
            include $tpl;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Template customer-form.php không tồn tại.', 'tmt-crm') . '</p></div>';
        }
    }

    /**
     * Handler: Save (Create/Update)
     */
    public static function handle_save(): void
    {
        error_log('SAVE as user_id=' . get_current_user_id());
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh
        if ($id > 0) {
            self::ensure_capability(Capability::CUSTOMER_UPDATE_ANY, __('Bạn không có quyền sửa khách hàng.', 'tmt-crm'));
        } else {
            self::ensure_capability(Capability::CUSTOMER_CREATE, __('Bạn không có quyền tạo khách hàng.', 'tmt-crm'));
        }

        // Kiểm nonce
        $nonce_name = $id > 0 ? 'tmt_crm_customer_update_' . $id : 'tmt_crm_customer_create';
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], $nonce_name)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Sanitize input
        $name     = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email    = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $phone    = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $address  = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $type     = sanitize_key(wp_unslash($_POST['type'] ?? ''));
        // ⭐ Người phụ trách: hiển thị tên, nhưng LƯU ID
        $owner_id = isset($_POST['owner_id']) && $_POST['owner_id'] !== '' ? absint($_POST['owner_id']) : 0;

        // Tạo DTO (created_at/updated_at để repo tự set)
        $dto = new CustomerDTO(
            $id ?: null,
            $name,
            $email ?: null,
            $phone ?: null,
            $address ?: null,
            $type ?: null,
            $owner_id ?: null
        );

        /** @var \TMT\CRM\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        try {
            if ($id > 0) {
                $svc->update($dto);
                AdminNoticeService::success_for_screen(
                    self::hook_suffix(),
                    __('Đã cập nhật khách hàng.', 'tmt-crm')
                );
            } else {
                $svc->create($dto);
                AdminNoticeService::success_for_screen(
                    self::hook_suffix(),
                    __('Đã tạo khách hàng.', 'tmt-crm')
                );
            }

            // URL sạch: trở về danh sách khách hàng
            wp_safe_redirect(self::url());
            exit;
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(
                self::hook_suffix(),
                sprintf(
                    /* translators: %s: error message */
                    __('Lưu thất bại: %s', 'tmt-crm'),
                    esc_html($e->getMessage())
                )
            );

            // Có thể quay lại list hoặc form; ở đây về list cho thống nhất
            wp_safe_redirect(self::url());
            exit;
        }
    }


    /** Handler: Delete (single) */
    public static function handle_delete(): void
    {
        self::ensure_capability(Capability::CUSTOMER_DELETE_ANY, __('Bạn không có quyền xoá khách hàng.', 'tmt-crm'));

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'tmt_crm_customer_delete_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('customer-service');

        try {
            $svc->delete($id);
            AdminNoticeService::error_for_screen(
                self::hook_suffix(),
                __('Đã xóa khách hàng.', 'tmt-crm')
            );
            wp_safe_redirect(self::url());
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw $e; // để dev thấy stack trace
            }
            AdminNoticeService::error_for_screen(self::hook_suffix(), __('Xóa khách hàng thất bại.', 'tmt-crm'));
            wp_safe_redirect(self::url());
            exit;
        }
    }

    /* ===================== Helpers ===================== */

    /** Build URL admin.php?page=tmt-crm-customers + $args */
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
