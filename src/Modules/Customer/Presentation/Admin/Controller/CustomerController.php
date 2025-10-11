<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Shared\Presentation\Support\AdminPostHelper;
use TMT\CRM\Shared\Presentation\Support\AdminNoticeService;
use TMT\CRM\Core\Capabilities\Application\Services\PolicyService;
use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;

defined('ABSPATH') || exit;

/**
 * Controller: xử lý request (admin-post, bulk, delete…)
 */
final class CustomerController
{


    /** Khởi động hook xử lý form (bootstrap — file chính) */
    public static function register(): void
    {
        // Lưu/Update
        add_action('admin_post_' . CustomerScreen::ACTION_SAVE,   [self::class, 'handle_save']);

        // Xoá mềm 1 bản ghi
        add_action('admin_post_' . CustomerScreen::ACTION_SOFT_DELETE, [self::class, 'handle_soft_delete']);

        // Xóa cứng
        add_action('admin_post_' . CustomerScreen::ACTION_HARD_DELETE, [self::class, 'handle_hard_delete']);

        //Xóa hàng loạt bản ghi
        add_action('admin_post_' . CustomerScreen::ACTION_BULK_DELETE, [self::class, 'handle_bulk_delete']);

        //Khôi phục bản ghi đã xóa mềm
        add_action('admin_post_' . CustomerScreen::ACTION_RESTORE, [self::class, 'handle_restore']);
    }


    /**
     * Handler: Save (Create/Update)
     */
    public static function handle_save(): void
    {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh
        if ($id > 0) {
            self::policy()->ensure_capability(
                Capability::CUSTOMER_UPDATE_ANY,
                get_current_user_id(),
                __('Bạn không có quyền sửa khách hàng.', 'tmt-crm')
            );
        } else {
            self::policy()->ensure_capability(
                Capability::CUSTOMER_CREATE,
                get_current_user_id(),
                __('Bạn không có quyền tạo khách hàng.', 'tmt-crm')
            );
        }

        // Nonce
        $nonce_name = $id > 0 ? 'tmt_crm_customer_update_' . $id : 'tmt_crm_customer_create';
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], $nonce_name)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Sanitize input
        $name      = sanitize_text_field($_POST['name'] ?? '');
        $phone     = sanitize_text_field($_POST['phone'] ?? '');
        $email     = sanitize_email($_POST['email'] ?? '');
        $address   = sanitize_textarea_field($_POST['address'] ?? '');
        $owner_id  = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : get_current_user_id();
        $note      = sanitize_textarea_field($_POST['note'] ?? '');

        if ($name === '') {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), __('Vui lòng nhập tên khách hàng.', 'tmt-crm'));
            wp_safe_redirect(CustomerScreen::url($id > 0 ? ['action' => 'edit', 'id' => $id] : ['action' => 'add']));
            exit;
        }

        /** @var TMT\CRM\Modules\Customer\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        try {
            $dto = CustomerDTO::from_array([
                'id'       => $id > 0 ? (int)$id : null,
                'name'     => (string)$name,
                'phone'    => (string)$phone,
                'email'    => (string)$email,
                'address'   => (string)$address,
                'owner_id' => (int)$owner_id,
                'note'     => (string)$note,
            ]);

            if ($id > 0) {
                $svc->update($id, $dto);
                AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã cập nhật khách hàng.', 'tmt-crm'));
                // Quay lại đúng trang (ưu tiên tham số back/_wp_http_referer)
                wp_safe_redirect(self::back_url());
                exit;
            } else {
                $svc->create($dto);
                AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã tạo khách hàng.', 'tmt-crm'));
                // Quay lại đúng trang (ưu tiên tham số back/_wp_http_referer)
                wp_safe_redirect(self::back_url());
                exit;
            }
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), $e->getMessage());
            wp_safe_redirect(CustomerScreen::url($id > 0 ? ['action' => 'edit', 'id' => $id] : ['action' => 'add']));
            exit;
        }
    }

    /**
     * Handler: Delete (single)
     */
    public static function handle_soft_delete(): void
    {
        self::policy()->ensure_capability(
            Capability::CUSTOMER_DELETE,
            get_current_user_id(),
            __('Bạn không có quyền xoá khách hàng.', 'tmt-crm')
        );

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }
        $nonce_action = CustomerScreen::NONCE_SOFT_DELETE  . $id;
        check_admin_referer($nonce_action); // ✅ khớp với khi tạo
        /** @var \TMT\CRM\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        try {
            $svc->soft_delete($id);
            AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã xóa khách hàng.', 'tmt-crm'));
            wp_safe_redirect(self::back_url());
            exit;
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), $e->getMessage());
            wp_safe_redirect(self::back_url());
            exit;
        }
    }
    /**
     * Handler: Bulk Delete nhiều khách hàng
     * Nhận từ form WP_List_Table (checkbox name="customer_ids[]")
     */
    public static function handle_bulk_delete(): void
    {
        self::policy()->ensure_capability(
            Capability::CUSTOMER_DELETE,
            get_current_user_id(),
            __('Bạn không có quyền xoá khách hàng.', 'tmt-crm')
        );

        // Nonce cho bulk
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], 'tmt_crm_customer_bulk_delete')) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Thu thập ID đã chọn
        $ids = array_map('absint', (array) ($_POST['customer_ids'] ?? []));
        $ids = array_values(array_filter($ids, fn($id) => $id > 0));

        if (empty($ids)) {
            AdminNoticeService::warning_for_screen(CustomerScreen::hook_suffix(), __('Chưa chọn bản ghi nào.', 'tmt-crm'));
            wp_safe_redirect(self::back_url());
            exit;
        }

        /** @var \TMT\CRM\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        $success = 0;
        $failed  = 0;

        foreach ($ids as $id) {
            try {
                $svc->delete($id); // hoặc soft-delete tuỳ chính sách
                $success++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        if ($success > 0) {
            AdminNoticeService::success_for_screen(
                CustomerScreen::hook_suffix(),
                sprintf(_n('Đã xoá %d khách hàng.', 'Đã xoá %d khách hàng.', $success, 'tmt-crm'), $success)
            );
        }
        if ($failed > 0) {
            AdminNoticeService::error_for_screen(
                CustomerScreen::hook_suffix(),
                sprintf(_n('%d bản ghi xoá thất bại.', '%d bản ghi xoá thất bại.', $failed, 'tmt-crm'), $failed)
            );
        }

        wp_safe_redirect(self::back_url());
        exit;
    }

    // ===== Helpers =====
    /** Tải PolicyService từ Container */
    private static function policy(): PolicyService
    {
        /** @var PolicyService $svc */
        $svc = Container::get('core.capabilities.policy_service');
        return $svc;
    }


    /**
     * Lấy URL quay lại (ưu tiên trường hidden "back" hoặc _wp_http_referer)
     */
    private static function back_url(array $fallback_query = []): string
    {
        if (isset($_REQUEST['back'])) {
            $back = esc_url_raw((string) wp_unslash($_REQUEST['back']));
            if ($back !== '') {
                return $back;
            }
        }
        $ref = wp_get_referer();
        if ($ref) {
            return $ref;
        }
        // Fallback về list screen, giữ state cơ bản nếu có
        return CustomerScreen::url($fallback_query);
    }
}
