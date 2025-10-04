<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Shared\Presentation\Support\AdminNoticeService;
use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;

defined('ABSPATH') || exit;

/**
 * Controller: xử lý request (admin-post, bulk, delete…)
 */
final class CustomerController
{
    /** Tên action cho admin-post */
    public const ACTION_SAVE   = 'tmt_crm_customer_save';
    public const ACTION_DELETE = 'tmt_crm_customer_delete';
    public const ACTION_BULK_DELETE  = 'tmt_crm_customer_bulk_delete'; // NEW

    /** Khởi động hook xử lý form (bootstrap — file chính) */
    public static function register(): void
    {
        // Lưu/Update
        add_action('admin_post_' . self::ACTION_SAVE,   [self::class, 'handle_save']);
        // Xoá 1 bản ghi
        add_action('admin_post_' . self::ACTION_DELETE, [self::class, 'handle_delete']);
        // Xóa hàng loạt
        add_action('admin_post_' . self::ACTION_BULK_DELETE, [self::class, 'handle_bulk_delete']); // NEW
    }


    /**
     * Handler: Save (Create/Update)
     */
    public static function handle_save(): void
    {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Phân quyền theo ngữ cảnh
        if ($id > 0) {
            self::ensure_capability(Capability::CUSTOMER_UPDATE_ANY, __('Bạn không có quyền sửa khách hàng.', 'tmt-crm'));
        } else {
            self::ensure_capability(Capability::CUSTOMER_CREATE, __('Bạn không có quyền tạo khách hàng.', 'tmt-crm'));
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
        $owner_id  = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;
        $note      = sanitize_textarea_field($_POST['note'] ?? '');

        if ($name === '') {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), __('Vui lòng nhập tên khách hàng.', 'tmt-crm'));
            self::redirect(CustomerScreen::url($id > 0 ? ['action' => 'edit', 'id' => $id] : ['action' => 'add']));
        }

        /** @var TMT\CRM\Modules\Customer\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        try {
            $dto = CustomerDTO::from_array([
                'id'       => $id > 0 ? (int)$id : null,
                'name'     => (string)$name,
                'phone'    => (string)$phone,
                'email'    => (string)$email,
                'owner_id' => ($owner_id ?? 0) > 0 ? (int)$owner_id : null,
                'note'     => (string)$note,
            ]);

            if ($id > 0) {
                $svc->update($id, $dto);
                AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã cập nhật khách hàng.', 'tmt-crm'));
            } else {
                $svc->create($dto);
                AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã tạo khách hàng.', 'tmt-crm'));
            }

            self::redirect(CustomerScreen::url());
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), $e->getMessage());
            self::redirect(CustomerScreen::url($id > 0 ? ['action' => 'edit', 'id' => $id] : ['action' => 'add']));
        }
    }

    /**
     * Handler: Delete (single)
     */
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

        /** @var \TMT\CRM\Application\Services\CustomerService $svc */
        $svc = Container::get('customer-service');

        try {
            $svc->delete($id);
            AdminNoticeService::success_for_screen(CustomerScreen::hook_suffix(), __('Đã xóa khách hàng.', 'tmt-crm'));
            self::redirect(CustomerScreen::url());
        } catch (\Throwable $e) {
            AdminNoticeService::error_for_screen(CustomerScreen::hook_suffix(), $e->getMessage());
            self::redirect(CustomerScreen::url());
        }
    }
    /**
     * Handler: Bulk Delete nhiều khách hàng
     * Nhận từ form WP_List_Table (checkbox name="customer_ids[]")
     */
    public static function handle_bulk_delete(): void
    {
        self::ensure_capability(
            Capability::CUSTOMER_DELETE_ANY,
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
            self::redirect(self::back_or_screen_url());
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

        self::redirect(self::back_or_screen_url());
    }

    // ===== Helpers =====

    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message);
        }
    }

    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
