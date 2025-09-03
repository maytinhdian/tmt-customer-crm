<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Controller;

use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Application\Services\CompanyContactService;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Shared\Container;

final class CompanyContactController
{
    public const ACTION_ATTACH = 'tmt_crm_company_contact_attach';
    private const NONCE_PREFIX = 'tmt_crm_company_contact_attach_';

    /** Đăng ký hook: gọi sớm ở bootstrap (file chính) */
    public static function register(): void
    {
        add_action('admin_post_' . self::ACTION_ATTACH, [self::class, 'insert']);
        // Nếu muốn cho khách chưa login:
        // add_action('admin_post_nopriv_' . self::ACTION_ATTACH, [self::class, 'insert']);
    }

    /** POST /wp-admin/admin-post.php?action=tmt_crm_company_contact_attach */
    public static function insert(): void
    {
        // --- Bảo mật & quyền ---
        $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
        check_admin_referer(self::NONCE_PREFIX . $company_id);

        if (!current_user_can(Capability::COMPANY_CREATE)) {
            wp_die(__('Bạn không có quyền thực hiện thao tác này.', 'tmt-crm'), 403);
        }

        // --- Lấy dữ liệu form & sanitize ---
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        $role        = isset($_POST['role']) ? sanitize_text_field((string)$_POST['role']) : '';
        $title    = isset($_POST['title']) ? sanitize_text_field((string)$_POST['title']) : '';
        $start_date  = sanitize_text_field((string)($_POST['start_date'] ?? '')) ?: wp_date('Y-m-d');
        $is_primary  = !empty($_POST['is_primary']);
        $note        = isset($_POST['note']) ? sanitize_text_field((string)$_POST['note']) : '';

        if ($company_id <= 0 || $customer_id <= 0) {
            self::redirect_back($company_id, 'error', __('Thiếu dữ liệu bắt buộc.', 'tmt-crm'));
        }

        // --- Build DTO từ Application\DTO ---
        $dto = new CompanyContactDTO(
            company_id: $company_id,
            customer_id: $customer_id,
            role: $role,
            title: $title,
            start_date: $start_date,
            is_primary: $is_primary,
            created_by: get_current_user_id()
        );

        // Một số DTO gốc có thể có 'note' → nếu có setter / to_array sẽ mang theo
        if (property_exists($dto, 'note')) {
            $dto->note = $note;
        }

        /** @var CompanyContactService $svc */
        $svc = Container::get('company-contact-service');

        try {
            $relation_id = $svc->insert_customer_for_company($dto);
            self::redirect_back($company_id, 'success', sprintf(
                /* translators: %d: relation id */
                __('Thêm khách liên hệ thành công (ID #%d).', 'tmt-crm'),
                $relation_id
            ));
        } catch (\Throwable $e) {
            error_log('[TMT CRM] company-contact attach error: ' . $e->getMessage());
            self::redirect_back($company_id, 'error', $e->getMessage());
        }
    }

    /** Điều hướng về tab Contacts của Company */
    private static function redirect_back(int $company_id, string $status, string $message): void
    {
        $url = add_query_arg([
            'page'       => 'tmt-crm-company',
            'tab'        => 'contacts',
            'company_id' => $company_id,
            'status'     => $status,
            'msg'        => rawurlencode($message),
        ], admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }
}
