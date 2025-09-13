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
    public const ACTION_BULK_DELETE = 'tmt_crm_company_bulk_delete';


    private const TABS = [
        'overview'    => 'Tổng quan',
        'contacts'    => 'Liên hệ',
        'notes-files' => 'Ghi chú/Tài liệu',
        // thêm các tab khác nếu cần...
    ];


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
        switch ($action) {
            case 'add':
                self::ensure_capability(Capability::COMPANY_CREATE, __('Bạn không có quyền tạo công ty.', 'tmt-crm'));
                self::render_form();
                break;

            case 'edit':
                self::ensure_capability(Capability::COMPANY_UPDATE, __('Bạn không có quyền sửa công ty.', 'tmt-crm'));
                $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
                self::render_form($id);
                break;

            case 'list':
            default:
                // Kể từ đây chuyển qua render theo tab
                $tab        = sanitize_key($_GET['tab'] ?? '');
                $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;

                // Ép URL luôn có tab=overview cho chuẩn
                if ($tab === '') {
                    wp_safe_redirect(self::tab_url('overview'));
                    exit;
                }

                echo '<div class="wrap">';
                echo '<h1 class="wp-heading-inline">' . esc_html__('Danh sách công ty', 'tmt-crm') . '</h1>';

                self::render_tab_nav($company_id, $tab);
                self::render_tab_content($company_id, $tab);

                echo '</div>';
                return;
        }
    }
    /** LIST VIEW: dùng CompanyListTable + bulk delete (giữ nguyên flow hiện tại) */
    public static function render_list(): void
    {
        // Nạp dữ liệu + phân trang
        $table = new CompanyListTable();
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

    private static function render_tab_nav(int $company_id, string $active_tab): void
    {
        echo '<h2 class="nav-tab-wrapper" style="margin-top:12px">';
        foreach (self::TABS as $slug => $label) {
            $class = ($slug === $active_tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $url   = self::tab_url($slug, ['company_id' => $company_id]);
            printf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($label)
            );
        }
        echo '</h2>';
    }

    private static function render_tab_content(int $company_id, string $tab): void
    {
        switch ($tab) {
            case 'contacts':
                \TMT\CRM\Presentation\Admin\Screen\CompanyContactsScreen::render_manage($company_id);
                break;

            case 'notes-files':
                // Sprint 1: Notes/Files tab
                \TMT\CRM\Presentation\Admin\Screen\CompanyNotesFilesScreen::render($company_id);
                break;

            case 'overview':
            default:
                // Dời list vào tab "Tổng quan"
                self::render_list_inner();
                break;
        }
    }
    /** Chỉ in phần nội dung list (không bọc <div class="wrap">) */
    private static function render_list_inner(): void
    {
        $table = new CompanyListTable();
        $table->prepare_items();

        $add_url = self::url(['action' => 'add']);

        // Header dòng trên tabs (tuỳ ý giữ/đổi)
        echo '<div style="margin:12px 0;">';
        if (current_user_can(Capability::COMPANY_CREATE)) {
            printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url($add_url),
                esc_html__('Thêm mới', 'tmt-crm')
            );
        }
        echo '</div>';
    ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_BULK_DELETE); ?>" />
            <?php
            $table->search_box(__('Tìm kiếm công ty', 'tmt-crm'), 'company');
            $table->display();
            wp_nonce_field('bulk-companies');
            ?>
        </form>
<?php
    }

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
    /** Build base URL admin.php?page=... + args */
    private static function base_url(array $args = []): string
    {
        $base = admin_url('admin.php');
        $args = array_merge(['page' => self::PAGE_SLUG], $args);
        return add_query_arg($args, $base);
    }

    /** Build URL chuyển tab, giữ state hiện tại */
    private static function tab_url(string $tab, array $args = []): string
    {
        $keep = [
            'company_id' => isset($_GET['company_id']) ? absint($_GET['company_id']) : null,
            'paged'      => isset($_GET['paged']) ? absint($_GET['paged']) : null,
            's'          => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : null,
        ];
        $keep = array_filter($keep, fn($v) => $v !== null && $v !== '');

        return self::base_url(array_merge($keep, $args, ['tab' => $tab]));
    }
}
