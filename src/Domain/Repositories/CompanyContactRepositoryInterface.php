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

  /**
   * Liệt kê liên hệ theo công ty (phân trang/tìm kiếm/sắp xếp tại DB).
   *
   * $args:
   * - search   : string|null (tìm trong role/title/phone/email)
   * - status   : 'all'|'active'|'ended' — lọc theo thời điểm hiện tại suy từ start_date/end_date (không phải cột DB).
   * - orderby  : 'customer'|'role'|'title'|'is_primary'|'start_date'|'end_date' (mặc định 'customer')
   * - order    : 'asc'|'desc' (mặc định 'asc')
   * - page     : int (>=1) (mặc định 1)
   * - per_page : int (mặc định 10)
   *
   * @return array{items: CompanyContactDTO[], total: int}
   */
  public function list_by_company(int $company_id, array $args = []): array;
}
