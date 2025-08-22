<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\ListTable\CustomerListTable;
use TMT\CRM\Application\DTO\CustomerDTO;

defined('ABSPATH') || exit;

/**
 * Màn hình quản trị: Khách hàng (Customers)
 */
final class CustomerScreen
{
    /** Slug trang customers trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-customers';

    /** Tên action cho admin-post */
    public const ACTION_SAVE   = 'tmt_crm_customer_save';
    public const ACTION_DELETE = 'tmt_crm_customer_delete';

    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_customers_per_page';

    /**
     * Đăng ký các handler admin_post (submit form)
     */
    public static function boot(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_DELETE, [self::class, 'handle_delete']);
    }

    /**
     * Được gọi khi load trang Customers để in Screen Options (per-page)
     */
    public static function on_load_customers(): void
    {
        if (!current_user_can(Capability::MANAGE)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số khách hàng mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);
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

    /**
     * Router view theo tham số ?action=...
     */
    public static function dispatch(): void
    {
        self::ensure_capability(Capability::MANAGE, __('Bạn không có quyền truy cập danh sách khách hàng.', 'tmt-crm'));

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : 'list';

        if ($action === 'add') {
            self::ensure_capability(Capability::CREATE, __('Bạn không có quyền tạo khách hàng.', 'tmt-crm'));
            self::render_form();
            return;
        }

        if ($action === 'edit') {
            self::ensure_capability(Capability::EDIT, __('Bạn không có quyền sửa khách hàng.', 'tmt-crm'));
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            self::render_form($id);
            return;
        }

        self::render_list();
    }

    /**
     * LIST VIEW: dùng CustomerListTable + bulk delete
     */
    public static function render_list(): void
    {
        $table = new CustomerListTable();

        // Bulk delete (nếu list-table submit)
        if (current_user_can(Capability::DELETE) && $table->current_action() === 'bulk-delete') {
            check_admin_referer('bulk-customers');

            $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
            $ids = array_values(array_filter(array_map('absint', $ids)));

            if (!empty($ids)) {
                $svc = Container::get('customer-service');
                foreach ($ids as $id) {
                    try {
                        $svc->delete($id);
                    } catch (\Throwable $e) {
                        // nuốt lỗi từng item để tiếp tục xóa các item khác
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
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã tạo khách hàng.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã cập nhật khách hàng.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Đã xoá %s khách hàng.', 'tmt-crm'), (int) $_GET['deleted']) . '</p></div>';
        }
        if (isset($_GET['error']) && !empty($_GET['msg'])) {
            echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash((string) $_GET['msg'])) . '</p></div>';
        }

        $add_url = self::url(['action' => 'add']);
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách khách hàng', 'tmt-crm'); ?></h1>
            <?php if (current_user_can(Capability::CREATE)) : ?>
                <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php esc_html_e('Thêm mới', 'tmt-crm'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end" />

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>" />
                <?php
                // Ô search native
                $table->search_box(__('Tìm kiếm khách hàng', 'tmt-crm'), 'customer');
                // Bảng
                $table->display();
                // Nonce cho bulk
                wp_nonce_field('bulk-customers');
                ?>
            </form>
        </div>
<?php
    }

    /**
     * FORM VIEW: Add/Edit
     */
    public static function render_form(int $id = 0): void
    {
        $svc = Container::get('customer-service');
        $customer = null;

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
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh: tạo hay cập nhật
        if ($id > 0) {
            self::ensure_capability(Capability::EDIT, __('Bạn không có quyền sửa khách hàng.', 'tmt-crm'));
        } else {
            self::ensure_capability(Capability::CREATE, __('Bạn không có quyền tạo khách hàng.', 'tmt-crm'));
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
        $company  = sanitize_text_field(wp_unslash($_POST['company'] ?? ''));
        $address  = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $note     = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        $type     = sanitize_key(wp_unslash($_POST['type'] ?? ''));
        $owner_id = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;

        // Tạo DTO (created_at/updated_at để repo tự set)
        $dto = new CustomerDTO(
            $id ?: null,            // ?int $id
            $name,                  // string $name
            $email ?: null,         // ?string $email
            $phone ?: null,         // ?string $phone
            $company ?: null,       // ?string $company
            $address ?: null,       // ?string $address
            $note ?: null,          // ?string $note
            $type ?: null,          // ?string $type
            $owner_id ?: null       // ?int $owner_id
        );

        $svc = Container::get('customer-service');

        try {
            if ($id > 0) {
                $svc->update($dto);
                self::redirect(self::url(['updated' => 1]));
            } else {
                $svc->create($dto);
                self::redirect(self::url(['created' => 1]));
            }
        } catch (\Throwable $e) {
            self::redirect(self::url([
                'error' => 1,
                'msg'   => rawurlencode($e->getMessage()),
            ]));
        }
    }

    /**
     * Handler: Delete (single)
     */
    public static function handle_delete(): void
    {
        self::ensure_capability(Capability::DELETE, __('Bạn không có quyền xoá khách hàng.', 'tmt-crm'));

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
            self::redirect(self::url(['deleted' => 1]));
        } catch (\Throwable $e) {
            self::redirect(self::url([
                'error' => 1,
                'msg'   => rawurlencode($e->getMessage()),
            ]));
        }
    }

    /* ===================== Helpers ===================== */

    /**
     * Build URL admin.php?page=tmt-crm-customers + $args
     */
    private static function url(array $args = []): string
    {
        $base = admin_url('admin.php');
        $args = array_merge(['page' => self::PAGE_SLUG], $args);
        return add_query_arg($args, $base);
    }

    /**
     * Kiểm tra quyền, nếu không đủ -> die với thông báo
     */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    /**
     * Redirect & exit
     */
    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
