<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Domain\ValueObject\CompanyContactRole;
use TMT\CRM\Shared\Container;

final class CompanyContactService
{
    public function __construct(

        private CompanyContactRepositoryInterface $repo

    ) {}

    /** Thêm hoặc cập nhật liên hệ */
    public function save_contact(CompanyContactDTO $dto, bool $set_primary = false): int
    {
        if (!CompanyContactRole::is_valid($dto->role)) {
            throw new \InvalidArgumentException("Role không hợp lệ: " . $dto->role);
        }

        if ($set_primary) {
            $this->repo->clear_primary_for_role($dto->company_id, $dto->role);
            $dto->is_primary = true;
        }

        $dto->updated_at = current_time('mysql');
        if (!$dto->id) {
            $dto->created_at = current_time('mysql');
        }

        return $this->repo->upsert($dto);
    }

    /** Lấy liên hệ chính cho role */
    public function get_primary_contact(int $company_id, string $role): ?CompanyContactDTO
    {
        return $this->repo->get_primary_contact($company_id, $role);
    }

    /** Danh sách liên hệ đang active */
    public function get_active_contacts(int $company_id, ?string $role = null): array
    {
        return $this->repo->find_active_contacts_by_company($company_id, $role);
    }

    /** Kết thúc liên hệ (set end_date) */
    public function end_contact(int $id, string $end_date): bool
    {
        return $this->repo->end_contact($id, $end_date);
    }
    
}

add_action('admin_post_tmt_crm_company_contact_detach', function () {
    if (!current_user_can('read')) wp_die('forbidden', 403);

    $company_id = (int)($_GET['company_id'] ?? 0);
    $contact_id = (int)($_GET['contact_id'] ?? 0);
    check_admin_referer('tmt_crm_company_contact_detach_' . $company_id . '_' . $contact_id);

    /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
    $svc = \TMT\CRM\Shared\Container::get('company-contact-service');
    $svc->detach_contact($company_id, $contact_id);

    wp_safe_redirect(admin_url('admin.php?page='
        . \TMT\CRM\Presentation\Admin\CompanyContactsScreen::PAGE_SLUG . '&company_id=' . $company_id));
    exit;
});

// (Trong CompanyContactService.php hoặc một Controller riêng)
add_action('admin_post_tmt_crm_company_contact_attach', function () {
    if (!current_user_can('read')) wp_die('forbidden', 403);

    $company_id  = (int)($_POST['company_id'] ?? 0);
    check_admin_referer('tmt_crm_company_contact_attach_' . $company_id);

    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $role        = sanitize_text_field($_POST['role'] ?? '');
    $position    = sanitize_text_field($_POST['position'] ?? '');
    $is_primary  = !empty($_POST['is_primary']) ? 1 : 0;
    $start_date  = sanitize_text_field($_POST['start_date'] ?? '');

    /** @var \TMT\CRM\Application\Services\CompanyContactService $svc */
    $svc = \TMT\CRM\Shared\Container::get('company-contact-service');
    $svc->attach_contact($company_id, $customer_id, $role, $position, $is_primary, $start_date);

    wp_safe_redirect(admin_url('admin.php?page='
        . \TMT\CRM\Presentation\Admin\CompanyContactsScreen::PAGE_SLUG . '&company_id=' . $company_id));
    exit;
});