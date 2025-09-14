<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact\Domain\Repositories;

use TMT\CRM\Modules\Contact\Application\DTO\CompanyContactDTO;

interface CompanyContactRepositoryInterface
{
  /**
   * Lấy danh sách liên hệ đang active của công ty (có thể lọc theo role).
   * Trả về mảng các hàng assoc/DTO tùy implementation.
   */
  public function find_active_contacts_by_company(int $company_id, ?string $role = null): array;

  /** @return CompanyContactDTO[] */
  public function find_paged_by_company(
    int $company_id,
    int $page,
    int $per_page,
    array $filters = [],
    array $sort = []
  ): array;

  public function find_by_id(int $id): ?CompanyContactDTO;

  /**
   * @return CompanyContactDTO[] Liên hệ của công ty
   */
  public function find_by_company(int $company_id): array;

  public function find_active_primary_by_company(int $company_id): ?CompanyContactDTO;

  public function get_company_name(int $company_id): string;

  /**
   * Chèn (attach) 1 customer vào công ty, trả về ID bản ghi quan hệ.
   */
  public function attach_customer(CompanyContactDTO $dto): int;

  /**
   * Gỡ liên hệ ra khỏi công ty (soft-detach): set end_date + is_primary = 0.
   * @param int         $company_id
   * @param int         $contact_id
   * @param string|null $end_date  'YYYY-MM-DD'; null => hôm nay theo timezone WP
   */
  public function detach(int $company_id, int $contact_id, ?string $end_date = null): bool;

  /**
   * Bỏ cờ primary của TẤT CẢ quan hệ trong 1 công ty.
   * Đặt 1 liên hệ làm liên hệ chính 
   * */
  public function set_primary(int $company_id, int $contact_id): bool;

  public function unset_primary(int $company_id): void;

  /** Bỏ is_primary (=NULL) cho toàn bộ liên hệ công ty */
  public function clear_primary(int $company_id): int;

  public function count_by_company(int $company_id, array $filters = []): int;

  /** Kiểm tra contact thuộc company (để ràng buộc dữ liệu) */
  public function contact_belongs_to_company(int $contact_id, int $company_id): bool;

  /** Ngày kết thúc < hôm nay thì xem như hết hiệu lực */
  public function is_contact_active(int $contact_id): bool;

  /**
   * Kiểm tra customer có đang active trong công ty không
   * (end_date IS NULL hoặc end_date >= CURDATE()).
   */
  public function is_customer_active_in_company(int $company_id, int $customer_id): bool;

  /**
   * Xoá cứng liên hệ (hard delete). Cân nhắc kỹ trước khi dùng.
   */
  public function delete(int $contact_id): bool;

  /**
   * Update liên hệ của công ty
   */
  public function update(CompanyContactDTO $d): bool;





  /** Transaction helpers (tuỳ DB) */
  public function begin(): void;
  public function commit(): void;
  public function roll_back(): void;
}
