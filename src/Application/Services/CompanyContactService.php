<?php

declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;

/**
 * Service xử lý nghiệp vụ thêm Customer vào CompanyContact.
 * - Không SQL/echo/redirect.
 * - Kiểm tra tồn tại company & customer.
 * - Chặn gắn trùng khi còn hiệu lực.
 * - Đảm bảo primary duy nhất nếu cần.
 */
final class CompanyContactService
{
    public function __construct(
        private CompanyContactRepositoryInterface $relation_repo,
        private CustomerRepositoryInterface       $customer_repo,
        private CompanyRepositoryInterface        $company_repo,
    ) {}

    /**
     * Hàm duy nhất cần dùng từ Controller:
     * Thêm (attach) 1 customer vào công ty → trả về ID quan hệ.
     */
    public function insert_customer_for_company(CompanyContactDTO $dto): int
    {
        $company_id  = $dto->company_id;
        $customer_id = $dto->customer_id;

        // 1) Validate tồn tại
        if ($company_id <= 0 || $this->company_repo->find_by_id($company_id) === null) {
            throw new \RuntimeException(__('Công ty không tồn tại.', 'tmt-crm'));
        }
        if ($customer_id <= 0 || $this->customer_repo->find_by_id($customer_id) === null) {
            throw new \RuntimeException(__('Khách liên hệ không tồn tại.', 'tmt-crm'));
        }

        // 2) Tránh gắn trùng đang active
        if ($this->relation_repo->is_customer_active_in_company($company_id, $customer_id)) {
            throw new \RuntimeException(__('Khách này đã được gắn vào công ty và đang còn hiệu lực.', 'tmt-crm'));
        }

        // 3) Đảm bảo primary duy nhất (nếu yêu cầu)
        if (!empty($dto->is_primary)) {
            $this->relation_repo->unset_primary($company_id);
        }

        // 4) Insert
        return $this->relation_repo->attach_customer($dto);
    }

    /**
     * Đặt 1 contact làm liên hệ chính của công ty.
     * - Reset các liên hệ khác về is_primary = 0.
     * - Set contact được chọn về is_primary = 1.
     */
    public function set_primary(int $company_id, int $contact_id): void
    {
        if ($company_id <= 0 || $contact_id <= 0) {
            throw new \InvalidArgumentException('company_id/contact_id không hợp lệ.');
        }
        $this->relation_repo->set_primary($company_id, $contact_id);
    }


    // CompanyContactService
    public function detach(int $company_id, int $contact_id, ?string $end_date = null): void
    {
        $this->relation_repo->detach($company_id, $contact_id, $end_date);
    }

    // Nếu muốn xoá cứng:
    public function delete(int $contact_id): void
    {
        $this->relation_repo->delete($contact_id);
    }
}
