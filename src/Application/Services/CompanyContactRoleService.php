<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyContactRoleDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRoleRepositoryInterface;
use TMT\CRM\Domain\Repositories\EmploymentRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;

/**
 * CompanyContactRoleService
 *
 * Quản lý vai trò liên hệ tại công ty theo thời gian.
 * - Gán vai trò (assign) cho 1 customer ở 1 company (yêu cầu customer đang active tại company)
 * - Kết thúc vai trò (end_role)
 * - Lấy role đang active theo company + role
 * - Liệt kê toàn bộ role đang active tại company
 *
 * Quy ước:
 * - Hàm snake_case.
 * - Tự kết thúc role cũ của cùng (company, role) khi assign role mới (end_date = start_date - 1).
 */
final class CompanyContactRoleService
{
    public const ROLE_ACCOUNTING = 'accounting';
    public const ROLE_PURCHASING = 'purchasing';
    public const ROLE_INVOICE    = 'invoice';

    private CompanyContactRoleRepositoryInterface $repo;
    private EmploymentRepositoryInterface $employment_repo;
    private CompanyRepositoryInterface $company_repo;
    private CustomerRepositoryInterface $customer_repo;

    public function __construct(
        CompanyContactRoleRepositoryInterface $repo,
        EmploymentRepositoryInterface $employment_repo,
        CompanyRepositoryInterface $company_repo,
        CustomerRepositoryInterface $customer_repo
    ) {
        $this->repo            = $repo;
        $this->employment_repo = $employment_repo;
        $this->company_repo    = $company_repo;
        $this->customer_repo   = $customer_repo;
    }

    /**
     * Gán 1 liên hệ vào 1 role tại company, hiệu lực từ $start_date (mặc định = hôm nay).
     * - Validate customer & company tồn tại.
     * - Validate customer đang active tại company (employment.end_date IS NULL).
     * - Tự end vai trò cũ (cùng company, role) nếu có.
     */
    public function assign_contact_role(int $company_id, int $customer_id, string $role, ?string $start_date = null): int
    {
        $start_date = $start_date ?: date('Y-m-d');
        $this->validate_role($role);
        $this->ensure_entities_exist($company_id, $customer_id);
        $this->ensure_customer_active_at_company($customer_id, $company_id);

        // End role cũ nếu có
        $current = $this->repo->get_active_by_role($company_id, $role);
        if ($current && isset($current['id'])) {
            $yesterday = date('Y-m-d', strtotime($start_date . ' -1 day'));
            $this->repo->end_role((int)$current['id'], $yesterday);
        }

        // Assign mới
        $dto = CompanyContactRoleDTO::from_array([
            'company_id'  => $company_id,
            'customer_id' => $customer_id,
            'role'        => $role,
            'start_date'  => $start_date,
            'end_date'    => null,
        ]);

        return $this->repo->assign_role($dto);
    }

    /**
     * Kết thúc một vai trò (set end_date).
     */
    public function end_contact_role(int $role_id, string $end_date): bool
    {
        if (!$this->is_valid_date($end_date)) {
            throw new \InvalidArgumentException('Invalid end_date format (Y-m-d expected)');
        }
        return $this->repo->end_role($role_id, $end_date);
    }

    /**
     * Liên hệ đang đảm nhiệm một role tại công ty.
     * Trả về mảng ['id'=>role_id,'role'=>..., 'customer'=>['id','name','phone','email']] | null
     */
    public function get_active_contact_by_role(int $company_id, string $role): ?array
    {
        $this->validate_role($role);
        return $this->repo->get_active_by_role($company_id, $role);
    }

    /**
     * Danh sách toàn bộ role đang active tại company.
     */
    public function list_active_contacts(int $company_id): array
    {
        return $this->repo->list_active($company_id);
    }

    /* ========================== Helpers ========================== */

    private function validate_role(string $role): void
    {
        // Cho phép mở rộng, ở đây chỉ kiểm tra không rỗng
        if (trim($role) === '') {
            throw new \InvalidArgumentException('role is required');
        }
    }

    private function ensure_entities_exist(int $company_id, int $customer_id): void
    {
        $company = $this->company_repo->find_by_id($company_id);
        if (!$company) {
            throw new \RuntimeException("Company #{$company_id} not found");
        }
        $customer = $this->customer_repo->find_by_id($customer_id);
        if (!$customer) {
            throw new \RuntimeException("Customer #{$customer_id} not found");
        }
    }

    private function ensure_customer_active_at_company(int $customer_id, int $company_id): void
    {
        $active = $this->employment_repo->get_active_by_customer($customer_id);
        if (!$active || $active->company_id !== $company_id) {
            throw new \RuntimeException("Customer #{$customer_id} is not active at company #{$company_id}");
        }
    }

    private function is_valid_date(string $d): bool
    {
        $t = \DateTime::createFromFormat('Y-m-d', $d);
        return $t && $t->format('Y-m-d') === $d;
    }
}
