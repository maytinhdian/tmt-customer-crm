<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Modules\Company\Presentation\Admin\Screen\CompanyScreen;
use TMT\CRM\Shared\Presentation\Support\AdminNoticeService;

final class CompanyController
{
    /** Đăng ký endpoints admin-post */
    public static function register(): void
    {
        add_action('admin_post_' . CompanyScreen::ACTION_SAVE,   [self::class, 'handle_save']);
        add_action('admin_post_' . CompanyScreen::ACTION_HARD_DELETE, [self::class, 'handle_hard_delete']);
        add_action('admin_post_' . CompanyScreen::ACTION_SOFT_DELETE, [self::class, 'handle_soft_delete']);
        add_action('admin_post_' . CompanyScreen::ACTION_RESTORE, [self::class, 'handle_restore']);
        add_action('admin_post_' . CompanyScreen::ACTION_BULK_DELETE, [self::class, 'handle_bulk_delete']);
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

        $representer = isset($_POST['representer'])
            ? sanitize_text_field(wp_unslash($_POST['representer']))
            : '';
        $representer = ($representer !== '') ? $representer : null;

        $data = [
            'name'        => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'tax_code'    => sanitize_text_field(wp_unslash($_POST['tax_code'] ?? '')),
            'address'     => sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')),
            'phone'       => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'email'       => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'website'     => esc_url_raw(wp_unslash($_POST['website'] ?? '')),
            'note'        => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
            'owner_id'    => $owner_id,
            'representer' => $representer,
        ];

        $svc = Container::get('company-service');

        try {
            if ($id > 0) {
                $svc->update($id, $data);
                AdminNoticeService::success_for_screen(
                    CompanyScreen::screen_id(),
                    __('Đã cập nhật công ty.', 'tmt-crm')
                );
            } else {
                $svc->create($data);
                AdminNoticeService::success_for_screen(
                    CompanyScreen::screen_id(),
                    __('Tạo mới công ty thành công.', 'tmt-crm')
                );
            }
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
            wp_safe_redirect(CompanyScreen::url(['tab' => $tab]));
            exit;
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(
                CompanyScreen::screen_id(),
                sprintf(__('Thao tác thất bại: %s', 'tmt-crm'), esc_html($e->getMessage()))
            );
            self::redirect(self::url(['error' => 1]));
        }
    }

    /** Handler: Delete - Purge (single) */
    public static function handle_hard_delete(): void
    {
        self::ensure_capability(Capability::COMPANY_DELETE, __('Bạn không có quyền xoá công ty.', 'tmt-crm'));

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'tmt_crm_company_purge_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('company-service');
        // Sanitize input
        $owner_id = get_current_user_id();
        try {
            $svc->purge($id, $owner_id);
            AdminNoticeService::success_for_screen(
                CompanyScreen::screen_id(),
                __('Xóa công ty thành công.', 'tmt-crm')
            );
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
            wp_safe_redirect(CompanyScreen::url(['tab' => $tab]));
            exit;
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(
                CompanyScreen::screen_id(),
                sprintf(__('Xóa thất bại: %s', 'tmt-crm'), esc_html($e->getMessage()))
            );
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
            wp_safe_redirect(CompanyScreen::url(['tab' => $tab]));
            exit;
        }
    }

    /** Handler: Soft Delete (single) */
    public static function handle_soft_delete(): void
    {
        self::ensure_capability(Capability::COMPANY_DELETE, __('Bạn không có quyền xoá công ty.', 'tmt-crm'));

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $actor_id = get_current_user_id(); // WP user đang thao tác

        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'tmt_crm_company_soft_delete_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('company-service');

        try {
            $svc->soft_delete($id, $actor_id);
            AdminNoticeService::success_for_screen(
                CompanyScreen::screen_id(),
                __('Xóa công ty thành công.', 'tmt-crm')
            );
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
            wp_safe_redirect(CompanyScreen::url(['tab' => $tab]));
            exit;
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(
                CompanyScreen::screen_id(),
                sprintf(__('Xóa thất bại: %s', 'tmt-crm'), esc_html($e->getMessage()))
            );
            self::redirect(self::url(['error' => 1]));
        }
    }

    /** Bulk delete nhiều công ty từ list table */
    public static function handle_bulk_delete(): void
    {
        self::ensure_capability(Capability::COMPANY_DELETE, __('Bạn không có quyền xoá công ty.', 'tmt-crm'));

        check_admin_referer('bulk-companies');

        $ids = array_map('absint', (array)($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($v) => $v > 0);

        if (empty($ids)) {
            wp_safe_redirect(self::url(['deleted' => 0]));
            exit;
        }

        $svc = Container::get('company-service');

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $svc->delete((int)$id);
                $deleted++;
            } catch (\Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[tmt-crm] bulk delete failed id=' . $id . ' msg=' . $e->getMessage());
                }
            }
        }

        AdminNoticeService::success_for_screen(
            CompanyScreen::screen_id(),
            sprintf(__('Đã xóa %d công ty.', 'tmt-crm'), $deleted)
        );

        wp_safe_redirect(self::url(['deleted' => $deleted]));
        exit;
    }

    //Khôi phục bản ghi bị xóa mềm ( soft-delete)
    public static function handle_restore(): void
    {
        self::ensure_capability(Capability::COMPANY_CREATE, __('Bạn không có quyền khôi phục công ty đã xóa.', 'tmt-crm'));

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $actor_id = get_current_user_id(); // WP user đang thao tác

        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'tmt_crm_company_restore_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('company-service');

        try {
            $svc->restore($id, $actor_id);
            AdminNoticeService::success_for_screen(
                CompanyScreen::screen_id(),
                __('Khôi phục công ty thành công.', 'tmt-crm')
            );
            self::redirect(self::url());
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(
                CompanyScreen::screen_id(),
                sprintf(__('Khôi phục thất bại: %s', 'tmt-crm'), esc_html($e->getMessage()))
            );
            self::redirect(self::url(['error' => 1]));
        }
    }
    // ================= Helpers =================

    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    /** Build URL admin.php?page=tmt-crm-companies + $args */
    private static function url(array $args = []): string
    {
        $base = admin_url('admin.php');
        $args = array_merge(['page' => CompanyScreen::PAGE_SLUG], $args);
        return add_query_arg($args, $base);
    }

    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
