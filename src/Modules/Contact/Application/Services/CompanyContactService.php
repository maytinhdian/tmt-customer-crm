<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact\Application\Services;

use TMT\CRM\Modules\Contact\Application\DTO\CompanyContactDTO;

use TMT\CRM\Modules\Company\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Modules\Contact\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;

use TMT\CRM\Modules\Contact\Application\Validation\CompanyContactValidator;

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
        private CompanyContactRepositoryInterface $contact_repo,
        private CustomerRepositoryInterface       $customer_repo,
        private CompanyRepositoryInterface        $company_repo,
        private CompanyContactValidator $validator
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
        if ($this->contact_repo->is_customer_active_in_company($company_id, $customer_id)) {
            throw new \RuntimeException(__('Khách này đã được gắn vào công ty và đang còn hiệu lực.', 'tmt-crm'));
        }

        // 3) Đảm bảo primary duy nhất (nếu yêu cầu)
        if ($dto->is_primary === 1) {
            $this->contact_repo->unset_primary($company_id);
        }

        // 4) Insert
        return $this->contact_repo->attach_customer($dto);
    }

    /**
     * Đặt liên hệ chính cho công ty:
     * - Validate: contact thuộc company & đang active
     * - Transaction: clear_primary -> set_primary
     * - DB UNIQUE (company_id, is_primary) bảo vệ mức hạ tầng
     */
    public function set_primary(int $company_id, int $customer_id): void
    {
        if ($company_id <= 0 || $customer_id <= 0) {
            throw new \InvalidArgumentException('company_id/customer_id không hợp lệ.');
        }

        $this->validator->ensure_contact_belongs_company($customer_id, $company_id);
        $this->validator->ensure_contact_active($customer_id);

        try {
            $this->contact_repo->begin();

            $this->contact_repo->clear_primary($company_id);
            
            if (!$this->contact_repo->set_primary($company_id, $customer_id)) {
                throw new \RuntimeException(__('Không đặt được liên hệ chính.', 'tmt-crm'));
            }

            $this->contact_repo->commit();
        } catch (\Throwable $e) {
            $this->contact_repo->roll_back();
            throw $e;
        }
    }


    public function update(CompanyContactDTO $d): void
    {
        // validate nhanh
        if ($d->id <= 0 || $d->company_id <= 0 || $d->customer_id <= 0) {
            throw new \InvalidArgumentException('Thiếu dữ liệu bắt buộc.');
        }
        // Không cho set end_date < start_date
        if ($d->start_date && $d->end_date && $d->end_date < $d->start_date) {
            throw new \InvalidArgumentException('end_date không thể nhỏ hơn start_date.');
        }

        $this->contact_repo->update($d);
    }


    // CompanyContactService
    public function detach(int $company_id, int $customer_id, ?string $end_date = null): void
    {
        $this->contact_repo->detach($company_id, $customer_id, $end_date);
    }

    public function unset_primary(int $company_id):void{
        $this->contact_repo->unset_primary($company_id);
    }
    // Nếu muốn xoá cứng:
    public function delete(int $customer_id): void
    {
        $this->contact_repo->delete($customer_id);
    }
}
