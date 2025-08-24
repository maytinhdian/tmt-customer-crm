<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyContactDTO;

interface CompanyContactRepositoryInterface
{
    public function find_by_id(int $id): ?CompanyContactDTO;
    public function find_active_contacts_by_company(int $company_id, ?string $role = null): array; // end_date NULL hoặc >= today
    public function get_primary_contact(int $company_id, string $role): ?CompanyContactDTO;
    public function upsert(CompanyContactDTO $dto): int;  // trả về id
    public function end_contact(int $id, string $end_date): bool; // kết thúc quan hệ
    public function delete(int $id): bool;
      /** Đặt tất cả liên hệ của cùng company+role về is_primary = 0 */
    public function clear_primary_for_role(int $company_id, string $role): void;
}
