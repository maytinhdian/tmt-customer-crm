<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Application\Services;

use TMT\CRM\Modules\Customer\Application\DTO\EmploymentHistoryDTO;
use TMT\CRM\Modules\Customer\Domain\Repositories\EmploymentHistoryRepositoryInterface;
// use TMT\CRM\Shared\Container;

final class EmploymentHistoryService
{
    public function __construct(
        private EmploymentHistoryRepositoryInterface $repo
    ) {}

    /** Thêm hoặc update lịch sử làm việc */
    public function save_history(EmploymentHistoryDTO $dto): int
    {
        $dto->updated_at = current_time('mysql');
        if (!$dto->id) {
            $dto->created_at = current_time('mysql');
        }

        return $this->repo->upsert($dto);
    }

    /** Lấy lịch sử theo customer */
    public function get_history(int $customer_id): array
    {
        return $this->repo->find_by_customer($customer_id);
    }

    /** Lấy công ty hiện tại của customer */
    public function get_current_company(int $customer_id): ?EmploymentHistoryDTO
    {
        return $this->repo->find_current_company_of_customer($customer_id);
    }

    /** Kết thúc việc làm */
    public function end_employment(int $id, string $end_date): bool
    {
        return $this->repo->end_employment($id, $end_date);
    }
    public function list_active_by_company(int $company_id): array
    {
        return $this->repo->list_active_by_company($company_id);
    }
}
