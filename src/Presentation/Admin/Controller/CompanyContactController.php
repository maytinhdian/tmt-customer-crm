<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container;
use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Application\Services\CompanyContactService;
use TMT\CRM\Presentation\Admin\Support\AdminNoticeService;
use TMT\CRM\Presentation\Admin\Screen\CompanyContactsScreen;


final class CompanyContactController
{
    public const ACTION_ATTACH = 'tmt_crm_company_contact_attach';
    public const ACTION_SET_PRIMARY = 'tmt_crm_company_contact_set_primary';
    public const ACTION_DETACH = 'tmt_crm_company_contact_detach';

    private const NONCE_PREFIX_ATTACH = 'tmt_crm_company_contact_attach_';
    private const NONCE_PREFIX_DETACH = 'tmt_crm_company_contact_detach_';
    private const NONCE_PREFIX_SET_PRIMARY = 'tmt_crm_company_contact_set_primary_';

    /** Đăng ký hook: gọi sớm ở bootstrap (file chính) */
    public static function register(): void
    {
        add_action('admin_post_' . self::ACTION_ATTACH, [self::class, 'insert']);

        // Nếu muốn cho khách chưa login:
        // add_action('admin_post_nopriv_' . self::ACTION_ATTACH, [self::class, 'insert']);

        add_action(
            'admin_post_' . self::ACTION_SET_PRIMARY,
            [self::class, 'set_primary']
        );
        add_action(
            'admin_post_' . self::ACTION_DETACH,
            [self::class, 'detach']
        );
    }

    /** POST /wp-admin/admin-post.php?action=tmt_crm_company_contact_attach */
    public static function insert(): void
    {
        // --- Bảo mật & quyền ---
        $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
        check_admin_referer(self::NONCE_PREFIX_ATTACH . $company_id);

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

        /** @var CompanyContactService $svc */
        $svc = Container::get('company-contact-service');

        try {
            $relation_id = $svc->insert_customer_for_company($dto);
            AdminNoticeService::success_for_screen(
                CompanyContactsScreen::hook_suffix(),
                sprintf(
                    /* translators: %d: relation id */
                    __('Thêm liên hệ thành công ( ID #%d ).', 'tmt-crm'),
                    $relation_id
                )
            );
            self::redirect_back($company_id, 'success');
        } catch (\Throwable $e) {
            error_log('[TMT CRM] company-contact attach error: ' . $e->getMessage());
            self::redirect_back($company_id, 'error', $e->getMessage());
            AdminNoticeService::success_for_screen(
                CompanyContactsScreen::hook_suffix(),
                sprintf(
                    /* translators: %s: error message */
                    __('Thao tác thất bại: %s', 'tmt-crm'),
                    esc_html($e->getMessage())
                )
            );
        }
    }

    public static function set_primary(): void
    {
        $customer_id = (int)($_REQUEST['customer_id'] ?? 0);
        $company_id = (int)($_REQUEST['company_id'] ?? 0);
        check_admin_referer(self::NONCE_PREFIX_SET_PRIMARY . $customer_id);
        self::ensure_capability(Capability::COMPANY_CREATE);

        try {
            /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
            $svc = Container::get('company-contact-service');
            $svc->set_primary($company_id, $customer_id);

            AdminNoticeService::success_for_screen('tmt-crm-company-contacts', __('Đã đặt liên hệ làm chính.', 'tmt-crm'));
            self::redirect_back($company_id, 'Success ...');
        } catch (\Throwable $e) {
            AdminNoticeService::success_for_screen(
                CompanyContactsScreen::hook_suffix(),
                sprintf(
                    /* translators: %s: error message */
                    __('Thao tác thất bại: %s', 'tmt-crm'),
                    esc_html($e->getMessage())
                )
            );
            // self::redirect_back($company_id,'');
        }
    }

    public static function detach(): void
    {
        $customer_id = (int)($_REQUEST['customer_id'] ?? 0);
        $company_id = (int)($_REQUEST['company_id'] ?? 0);
        check_admin_referer(self::NONCE_PREFIX_DETACH . $customer_id);
        self::ensure_capability(Capability::COMPANY_CREATE);

        try {
            /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
            $svc = Container::get('company-contact-service');
            $svc->detach($company_id, $customer_id);
            AdminNoticeService::success_for_screen(CompanyContactsScreen::hook_suffix(), __('Đã gỡ liên hệ.', 'tmt-crm'));
            self::redirect_back($company_id, 'Thành công');
        } catch (\Throwable $e) {
            AdminNoticeService::success_for_screen(
                CompanyContactsScreen::hook_suffix(),
                sprintf(
                    /* translators: %s: error message */
                    __('Thao tác thất bại: %s', 'tmt-crm'),
                    esc_html($e->getMessage())
                )
            );
        }
    }

    /** Điều hướng về tab Contacts của Company */
    private static function redirect_back(int $company_id, ?string $status): void
    {
        $url = add_query_arg([
            'page'       => 'tmt-crm-company-contacts',
            'company_id' => $company_id,
            'status'     => $status,
        ], admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }
    private static function ensure_capability(string $cap): void
    {
        if (! current_user_can($cap)) {
            wp_die(__('Bạn không có quyền thực hiện thao tác này.', 'tmt-crm'));
        }
    }
}
