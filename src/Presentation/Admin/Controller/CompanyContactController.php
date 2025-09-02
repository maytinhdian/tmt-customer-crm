<?php
declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\CompanyContactsScreen;

final class CompanyContactController
{
    /** Đăng ký các admin_post handler */
    public static function register(): void
    {
        add_action('admin_post_tmt_crm_company_contact_attach', [self::class, 'attach_contact']);
        add_action('admin_post_tmt_crm_company_contact_detach', [self::class, 'detach_contact']);
    }

    /** Gán (attach) 1 khách hàng làm liên hệ của công ty */
    public static function attach_contact(): void
    {
        self::ensure_capability(
            Capability::COMPANY_UPDATE,
            __('Bạn không có quyền gán liên hệ cho công ty.', 'tmt-crm')
        );

        $company_id = isset($_POST['company_id']) ? absint($_POST['company_id']) : 0;
        check_admin_referer('tmt_crm_company_contact_attach_' . $company_id);

        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $role        = sanitize_text_field($_POST['role'] ?? '');
        $position    = sanitize_text_field($_POST['position'] ?? '');
        $is_primary  = !empty($_POST['is_primary']);
        $start_date  = sanitize_text_field($_POST['start_date'] ?? '');

        /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
        $svc = Container::get('company-contact-service');
        // Gọi service theo nghiệp vụ dự án của bạn
        $svc->attach_contact($company_id, $customer_id, $role, $position, $is_primary, $start_date);

        self::redirect(
            admin_url('admin.php?page=' . CompanyContactsScreen::PAGE_SLUG . '&company_id=' . $company_id)
        );
    }

    /** Gỡ (detach) 1 liên hệ khỏi công ty */
    public static function detach_contact(): void
    {
        self::ensure_capability(
            Capability::COMPANY_UPDATE,
            __('Bạn không có quyền gỡ liên hệ khỏi công ty.', 'tmt-crm')
        );

        $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        $contact_id = isset($_GET['contact_id']) ? absint($_GET['contact_id']) : 0;
        check_admin_referer('tmt_crm_company_contact_detach_' . $company_id . '_' . $contact_id);

        /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
        $svc = Container::get('company-contact-service');
        $svc->detach_contact($company_id, $contact_id);

        self::redirect(
            admin_url('admin.php?page=' . CompanyContactsScreen::PAGE_SLUG . '&company_id=' . $company_id)
        );
    }

    /** Kiểm tra quyền, thiếu quyền -> die (đúng “dạng” bạn yêu cầu) */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            wp_die($message, __('Không có quyền', 'tmt-crm'), ['response' => 403]);
        }
    }

    /** Redirect & exit gọn gàng */
    private static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }
}
