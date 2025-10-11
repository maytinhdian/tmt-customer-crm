<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Modules\Customer\Presentation\Admin\ListTable\CustomerListTable;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;

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
    public const ACTION_HARD_DELETE = 'tmt_crm_customer_purge';
    public const ACTION_SOFT_DELETE = 'tmt_crm_customer_soft_delete';
    public const ACTION_BULK_DELETE = 'tmt_crm_customer_bulk_delete';
    public const ACTION_RESTORE = 'tmt_crm_customer_restore';

    /** Nonces cho admin-post */
    public const NONCE_SAVE   = 'tmt_crm_customer_save_';
    public const NONCE_HARD_DELETE = 'tmt_crm_customer_purge_';
    public const NONCE_SOFT_DELETE = 'tmt_crm_customer_soft_delete_';
    public const NONCE_BULK_DELETE = 'tmt_crm_customer_bulk_delete_';
    public const NONCE_RESTORE = 'tmt_crm_customer_restore_';


    /** Tên option Screen Options: per-page */
    public const OPTION_PER_PAGE = 'tmt_crm_customers_per_page';

    /** Đăng ký các handler admin_post (submit form) */
    public static function boot(): void
    {
        // add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        // add_action('admin_post_' . self::ACTION_SOFT_DELETE, [self::class, 'handle_delete']);
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

        // Nạp dữ liệu + phân trang
        $table->prepare_items();

        $add_url = self::url(['action' => 'add']); ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách khách hàng', 'tmt-crm'); ?></h1>
            <?php if (current_user_can(Capability::CUSTOMER_CREATE)) : ?>
                <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php esc_html_e('Thêm mới', 'tmt-crm'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end" />
            <?php
            // 👇 PHẢI có dòng này để hiện các link (views): All / Active / Trash...
            $table->views();
            ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>" />
                <?php
                $table->search_box(__('Tìm kiếm khách hàng', 'tmt-crm'), 'tmt-crm-customers-search-input');
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
        /** @var \TMT\CRM\Shared\Container\Container $c */
        $svc = \TMT\CRM\Shared\Container\Container::get('customer-service');

        /** @var \TMT\CRM\Domain\Repositories\UserRepositoryInterface $user_repo */
        $user_repo = \TMT\CRM\Shared\Container\Container::get('user-repo');

        /** @var \TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO|null $customer */
        $customer = null;

        if ($id > 0) {
            $customer = $svc->get_by_id($id);
            if (!$customer) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Không tìm thấy khách hàng.', 'tmt-crm') . '</p></div>';
                return;
            }
        }

        // ✅ Giá trị mặc định (sau khi đã có $customer)
        $owner_id_selected = (int)($customer?->owner_id ?? get_current_user_id());

        // ✅ Chuẩn bị owner choices (có thể thay bằng $user_repo nếu bạn đã có hàm riêng)
        $owner_choices = [];
        foreach (get_users(['fields' => ['ID', 'display_name']]) as $u) {
            $owner_choices[(int)$u->ID] = (string)$u->display_name;
        }

        // ✅ Nonce name dùng khi submit form (tuỳ convenion của bạn)
        $nonce_name = 'tmt_crm_customer_save';

        // ✅ Render qua View helper
        $module   = 'customer';
        $template = 'customer-form';

        // Back URL: ưu tiên trang trước đó, fallback về danh sách khách hàng
        $back_url = wp_get_referer();
        if (!$back_url || strpos((string)$back_url, 'page=tmt-crm-customer') !== false) {
            // Đổi slug bên dưới cho đúng với màn danh sách của bạn
            $back_url = admin_url('admin.php?page=tmt-crm-customers');
        }

        if (\TMT\CRM\Shared\Presentation\Support\View::exists_admin($module . '\\' . $template)) {
            \TMT\CRM\Shared\Presentation\Support\View::render_admin_module($module, $template, [
                'customer'          => $customer,
                'nonce_name'        => $nonce_name,
                'owner_choices'     => $owner_choices,
                'owner_id_selected' => $owner_id_selected,
                'back_url'          => $back_url,
            ]);
            return;
        }

        echo '<div class="notice notice-error"><p>' .
            esc_html__('Template customer-form không tồn tại trong View.', 'tmt-crm') .
            '</p></div>';
    }


    /* ===================== Helpers ===================== */

    /** Build URL admin.php?page=tmt-crm-customers + $args */
    public static function url(array $args = []): string
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
