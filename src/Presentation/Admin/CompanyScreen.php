<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Application\DTO\CompanyDTO;

defined('ABSPATH') || exit;

/**
 * Màn hình quản trị: Công ty (Companies)
 * - Không dùng ListTable ở giai đoạn này (list chỉ là placeholder).
 * - Hỗ trợ: add/edit form, save, delete.
 */
final class CompanyScreen
{
    /** Slug trang companies trên admin.php?page=... */
    public const PAGE_SLUG = 'tmt-crm-companies';

    /** Tên action cho admin-post */
    public const ACTION_SAVE   = 'tmt_crm_company_save';
    public const ACTION_DELETE = 'tmt_crm_company_delete';

    /** Tùy chọn Screen Options: per-page (để dành tương lai) */
    public const OPTION_PER_PAGE = 'tmt_crm_companies_per_page';

    /**
     * Đăng ký handler admin_post (submit form)
     * Gọi từ bootstrap: add_action('admin_init', [CompanyScreen::class, 'boot']);
     */
    public static function boot(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_DELETE, [self::class, 'handle_delete']);
    }

    /**
     * (Optional) Khi load trang Companies → hiển thị Screen Options (per-page)
     * Hiện chưa dùng ListTable nên tuỳ chọn này chỉ để sẵn.
     */
    public static function on_load_companies(): void
    {
        if (!current_user_can(Capability::MANAGE)) {
            return;
        }

        add_screen_option('per_page', [
            'label'   => __('Số công ty mỗi trang', 'tmt-crm'),
            'default' => 20,
            'option'  => self::OPTION_PER_PAGE,
        ]);
    }

    /** Lưu Screen Options (per-page) — để sẵn, tương lai có ListTable thì dùng */
    public static function save_screen_option($status, $option, $value)
    {
        if ($option === self::OPTION_PER_PAGE) {
            return (int) $value;
        }
        return $status;
    }

    /**
     * Router view theo ?action=...
     * - list: placeholder
     * - add:  form thêm
     * - edit: form sửa
     */
    public static function dispatch(): void
    {
        self::ensure_capability(
            Capability::MANAGE,
            __('Bạn không có quyền truy cập danh sách công ty.', 'tmt-crm')
        );

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : 'list';

        if ($action === 'add') {
            self::ensure_capability(Capability::CREATE, __('Bạn không có quyền tạo công ty.', 'tmt-crm'));
            self::render_form();
            return;
        }

        if ($action === 'edit') {
            self::ensure_capability(Capability::EDIT, __('Bạn không có quyền sửa công ty.', 'tmt-crm'));
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            self::render_form($id);
            return;
        }

        self::render_list_placeholder();
    }

    /**
     * LIST VIEW (placeholder)
     * Chưa triển khai ListTable, tạm hiển thị nút "Thêm mới" & thông báo.
     */
    public static function render_list_placeholder(): void
    {
        // Notices
        if (isset($_GET['created'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã tạo công ty.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã cập nhật công ty.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã xoá công ty.', 'tmt-crm') . '</p></div>';
        }
        if (isset($_GET['error']) && !empty($_GET['msg'])) {
            echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash((string) $_GET['msg'])) . '</p></div>';
        }

        $add_url = self::url(['action' => 'add']);
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách công ty', 'tmt-crm'); ?></h1>
            <?php if (current_user_can(Capability::CREATE)) : ?>
                <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php esc_html_e('Thêm mới', 'tmt-crm'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end" />

            <div class="notice notice-info" style="margin-top:12px;">
                <p><?php esc_html_e('Tính năng danh sách công ty sẽ được bổ sung sau. Hiện bạn có thể thêm/sửa/xoá công ty bằng form.', 'tmt-crm'); ?></p>
            </div>
        </div>
<?php
    }

    /**
     * FORM VIEW: Add/Edit
     */
    public static function render_form(int $id = 0): void
    {
        $svc = Container::get('company-service');
        $company = null;

        if ($id > 0) {
            /** @var CompanyDTO|null $company */
            $company = $svc->get_by_id($id);
            if (!$company) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Không tìm thấy công ty.', 'tmt-crm') . '</p></div>';
                return;
            }
        }

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/company-form.php';
        if (file_exists($tpl)) {
            /** @var CompanyDTO|null $company */
            include $tpl;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Template company-form.php không tồn tại.', 'tmt-crm') . '</p></div>';
        }
    }

    /**
     * Handler: Save (Create/Update)
     */
    public static function handle_save(): void
    {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh
        if ($id > 0) {
            self::ensure_capability(Capability::EDIT, __('Bạn không có quyền sửa công ty.', 'tmt-crm'));
        } else {
            self::ensure_capability(Capability::CREATE, __('Bạn không có quyền tạo công ty.', 'tmt-crm'));
        }

        // Kiểm nonce
        $nonce_name = $id > 0 ? 'tmt_crm_company_update_' . $id : 'tmt_crm_company_create';
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], $nonce_name)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Sanitize input
        $name        = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $tax_code    = sanitize_text_field(wp_unslash($_POST['tax_code'] ?? ''));
        $email       = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $phone       = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $address     = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $representer = sanitize_text_field(wp_unslash($_POST['representer'] ?? '')); // người đại diện
        $website     = esc_url_raw(wp_unslash($_POST['website'] ?? ''));
        $note        = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        $owner_id    = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;

        if ($name === '') {
            wp_die(__('Tên công ty là bắt buộc.', 'tmt-crm'));
        }

        /**
         * Tạo DTO.
         * ⚠️ Điều chỉnh chữ ký nếu CompanyDTO của bạn khác:
         * Gợi ý: (?int $id, string $name, ?string $tax_code, ?string $email, ?string $phone, ?string $address, ?string $representer, ?string $website, ?string $note, ?int $owner_id)
         */
        $dto = new CompanyDTO(
            $id ?: null,
            $name,
            $tax_code ?: null,
            $email ?: null,
            $phone ?: null,
            $address ?: null,
            $representer ?: null,
            $website ?: null,
            $note ?: null,
            $owner_id ?: null
        );

        $svc = Container::get('company-service');

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
        self::ensure_capability(Capability::DELETE, __('Bạn không có quyền xoá công ty.', 'tmt-crm'));

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

    /** Kiểm tra quyền, thiếu quyền → die với thông báo */
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
