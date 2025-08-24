<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CustomerEmploymentDTO;

interface EmploymentRepositoryInterface
{
    public function create(CustomerEmploymentDTO $dto): int;
    public function close_employment(int $employment_id, string $end_date): bool;

    /** Đang làm việc (end_date IS NULL) */
    public function get_active_by_customer(int $customer_id): ?CustomerEmploymentDTO;

    /** Lịch sử theo customer */
    public function list_by_customer(int $customer_id): array;

    /** Danh sách người đang làm tại 1 công ty */
    public function list_active_by_company(int $company_id): array;
}
