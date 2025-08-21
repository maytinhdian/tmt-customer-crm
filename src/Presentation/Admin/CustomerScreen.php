<?php

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Shared\Container;
use TMT\CRM\Application\DTO\CustomerDTO;

defined('ABSPATH') || exit;

/**
 * CustomerScreen
 * - List: admin.php?page=tmt-crm-customers
 * - Add : admin.php?page=tmt-crm-customers&action=add
 * - Edit: admin.php?page=tmt-crm-customers&action=edit&id=123
 *
 * Submit:
 * - Save  : admin-post.php?action=tmt_crm_customer_save
 * - Delete: admin-post.php?action=tmt_crm_customer_delete&id=123&_wpnonce=...
 */
final class CustomerScreen
{
    /** Gọi trong Hooks::register() */
    public static function boot(): void
    {
        add_action('admin_post_tmt_crm_customer_save',   [self::class, 'handle_save']);
        add_action('admin_post_tmt_crm_customer_delete', [self::class, 'handle_delete']);
    }

    /** Điều phối view */
    public static function dispatch(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập.', 'tmt-crm'));
        }

        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        switch ($action) {
            case 'add':
                self::render_form();
                break;
            case 'edit':
                self::render_form(max(0, (int)($_GET['id'] ?? 0)));
                break;
            default:
                self::render_list();
        }
    }

    /** List View */
    public static function render_list(): void
    {
        $svc      = Container::get('customer-service');
        $page     = max(1, (int)($_GET['paged'] ?? 1));
        $per_page = 20;

        $filters = [
            'keyword'  => sanitize_text_field($_GET['s'] ?? ''),
            'type'     => sanitize_key($_GET['type'] ?? ''),
            'owner_id' => isset($_GET['owner']) ? absint($_GET['owner']) : null,
        ];

        $data      = $svc->list_customers($page, $per_page, $filters);
        $customers = $data['items'] ?? [];
        $total     = (int)($data['total'] ?? 0);

        $message = '';
        if (isset($_GET['created'])) $message = 'created';
        elseif (isset($_GET['updated'])) $message = 'updated';
        elseif (isset($_GET['deleted'])) $message = 'deleted';
        elseif (isset($_GET['error']))   $message = 'error';

        $base_url = admin_url('admin.php?page=tmt-crm-customers');

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/customers-list.php';
        if (file_exists($tpl)) {
            /** @var array $filters */
            include $tpl;
        } else {
            echo '<div class="error"><p>Template customers-list.php không tồn tại.</p></div>';
        }
    }

    /** Form View (Add/Edit) */
    public static function render_form(int $id = 0): void
    {
        $svc = Container::get('customer-service');

        $customer = null;
        if ($id > 0) {
            $customer = $svc->get_by_id($id); // trả về CustomerDTO|null
        }

        $tpl = trailingslashit(TMT_CRM_PATH) . 'templates/admin/customer-form.php';
        if (file_exists($tpl)) {
            /** @var CustomerDTO|null $customer */
            include $tpl;
        } else {
            echo '<div class="error"><p>Template customer-form.php không tồn tại.</p></div>';
        }
    }

    /** Handler: Save (Create/Update) */
    // public static function handle_save(): void
    // {
    //     if (!current_user_can('manage_options')) {
    //         wp_die(__('Bạn không có quyền.', 'tmt-crm'));
    //     }

    //     $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    //     $nonce_name = $id > 0 ? 'tmt_crm_customer_update_'.$id : 'tmt_crm_customer_create';
    //     if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $nonce_name)) {
    //         wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
    //     }

    //     // Tạo DTO từ POST
    //     $dto = new CustomerDTO();
    //     $dto->id       = $id ?: null;
    //     $dto->name     = sanitize_text_field($_POST['name'] ?? '');
    //     $dto->email    = sanitize_email($_POST['email'] ?? '');
    //     $dto->phone    = sanitize_text_field($_POST['phone'] ?? '');
    //     $dto->company  = sanitize_text_field($_POST['company'] ?? '');
    //     $dto->type     = sanitize_key($_POST['type'] ?? '');
    //     $dto->owner_id = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : null;
    //     $dto->note     = sanitize_textarea_field($_POST['note'] ?? '');

    //     $svc = Container::get('customer-service');

    //     try {
    //         if ($id > 0) {
    //             $svc->update($dto);
    //             $redirect = admin_url('admin.php?page=tmt-crm-customers&updated=1');
    //         } else {
    //             $svc->create($dto);
    //             $redirect = admin_url('admin.php?page=tmt-crm-customers&created=1');
    //         }
    //     } catch (\Throwable $e) {
    //         $redirect = admin_url('admin.php?page=tmt-crm-customers&error=1&msg=' . rawurlencode($e->getMessage()));
    //     }

    //     wp_safe_redirect($redirect);
    //     exit;
    // }
    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền.', 'tmt-crm'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $nonce_name = $id > 0 ? 'tmt_crm_customer_update_' . $id : 'tmt_crm_customer_create';
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $nonce_name)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        // Bóc tách & sanitize
        $name     = sanitize_text_field($_POST['name'] ?? '');
        $email    = sanitize_email($_POST['email'] ?? '');
        $phone    = sanitize_text_field($_POST['phone'] ?? '');
        $company  = sanitize_text_field($_POST['company'] ?? '');
        $address  = sanitize_text_field($_POST['address'] ?? ''); // thêm address để khớp DTO
        $note     = sanitize_textarea_field($_POST['note'] ?? '');
        $type     = sanitize_key($_POST['type'] ?? '');
        $owner_id = isset($_POST['owner_id']) ? absint($_POST['owner_id']) : 0;

        // Tạo DTO theo đúng constructor hiện có
        $dto = new \TMT\CRM\Application\DTO\CustomerDTO(
            $id ?: null,              // ?int $id
            $name,                    // string $name (bắt buộc)
            $email ?: null,           // ?string $email
            $phone ?: null,           // ?string $phone
            $company ?: null,         // ?string $company
            $address ?: null,         // ?string $address
            $note ?: null,            // ?string $note
            $type ?: null,            // ?string $type
            $owner_id ?: null,        // ?int $owner_id
            null,                     // ?string $created_at (để repo tự set)
            null                      // ?string $updated_at (để repo tự set)
        );

        $svc = \TMT\CRM\Shared\Container::get('customer-service');

        try {
            if ($id > 0) {
                $svc->update($dto);
                $redirect = admin_url('admin.php?page=tmt-crm-customers&updated=1');
            } else {
                $svc->create($dto);
                $redirect = admin_url('admin.php?page=tmt-crm-customers&created=1');
            }
        } catch (\Throwable $e) {
            $redirect = admin_url('admin.php?page=tmt-crm-customers&error=1&msg=' . rawurlencode($e->getMessage()));
        }

        wp_safe_redirect($redirect);
        exit;
    }

    
    /** Handler: Delete */
    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền.', 'tmt-crm'));
        }

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id <= 0) {
            wp_die(__('Thiếu ID.', 'tmt-crm'));
        }
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tmt_crm_customer_delete_' . $id)) {
            wp_die(__('Nonce không hợp lệ.', 'tmt-crm'));
        }

        $svc = Container::get('customer-service');

        try {
            $svc->delete($id);
            $redirect = admin_url('admin.php?page=tmt-crm-customers&deleted=1');
        } catch (\Throwable $e) {
            $redirect = admin_url('admin.php?page=tmt-crm-customers&error=1&msg=' . rawurlencode($e->getMessage()));
        }

        wp_safe_redirect($redirect);
        exit;
    }
}
