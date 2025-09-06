<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyContactDTO;

interface CompanyContactRepositoryInterface
{
  /**
   * Lấy danh sách liên hệ đang active của công ty (có thể lọc theo role).
   * Trả về mảng các hàng assoc/DTO tùy implementation.
   */
  public function find_active_contacts_by_company(int $company_id, ?string $role = null): array;
  /**
   * Chèn (attach) 1 customer vào công ty, trả về ID bản ghi quan hệ.
   */
  public function attach_customer(CompanyContactDTO $dto): int;

  /**
   * Kiểm tra customer có đang active trong công ty không
   * (end_date IS NULL hoặc end_date >= CURDATE()).
   */
  public function is_customer_active_in_company(int $company_id, int $customer_id): bool;

  /**
   * Bỏ cờ primary của TẤT CẢ quan hệ trong 1 công ty.
   */
  public function unset_primary(int $company_id): void;

  /** @return CompanyContactDTO[] */
  public function find_paged_by_company(
    int $company_id,
    int $page,
    int $per_page,
    array $filters = [],
    array $sort = []
  ): array;

  public function count_by_company(int $company_id, array $filters = []): int;
  public function get_company_name(int $company_id): string;
}
