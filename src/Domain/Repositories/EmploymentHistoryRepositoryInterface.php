<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\EmploymentHistoryDTO;

interface EmploymentHistoryRepositoryInterface
{
    public function find_by_id(int $id): ?EmploymentHistoryDTO;
    public function find_by_customer(int $customer_id): array; // toàn bộ lịch sử theo thời gian
    public function find_current_company_of_customer(int $customer_id): ?EmploymentHistoryDTO; // end_date NULL / >= today
    public function upsert(EmploymentHistoryDTO $dto): int;
    public function end_employment(int $id, string $end_date): bool;
    public function delete(int $id): bool;
    public function list_active_by_company(int $company_id): array;

}
