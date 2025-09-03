<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyContactDTO;

// interface CompanyContactRepositoryInterface
// {
//   /**
//    * Tạo quan hệ công ty - liên hệ.
//    * Trả về ID bản ghi quan hệ vừa tạo.
//    */
//   public function attach_contact(CompanyContactDTO $dto): int;

//   /**
//    * Kiểm tra liên hệ có đang active trong công ty không
//    * (end_date IS NULL hoặc end_date >= CURDATE()).
//    */
//   public function is_contact_active_in_company(int $company_id, int $contact_id): bool;

//   /**
//    * Bỏ cờ primary của TẤT CẢ liên hệ thuộc 1 công ty.
//    */
//   public function unset_primary(int $company_id): void;

//   /**
//    * Đặt primary cho 1 quan hệ (và tự bỏ primary các quan hệ khác trong công ty).
//    */
//   public function set_primary(int $relation_id): void;

//   /**
//    * Kết thúc hiệu lực của 1 quan hệ.
//    * - Nếu $end_date = null → dùng ngày hiện tại (Y-m-d theo múi giờ WP).
//    */
//   public function end_contact(int $relation_id, ?string $end_date = null): void;

//   /**
//    * Cập nhật meta của quan hệ (role, position, start_date, end_date, is_primary).
//    * - Không cho phép đổi company_id/contact_id bằng hàm này.
//    */
//   public function update_meta(int $relation_id, array $data): void;

//   /**
//    * Lấy 1 quan hệ theo ID. Trả về mảng assoc hoặc null nếu không có.
//    */
//   public function get_by_id(int $relation_id): ?array;

//   /**
//    * Lấy danh sách liên hệ đang active của công ty (có thể lọc theo role).
//    * Trả về mảng các hàng assoc/DTO tùy implementation.
//    */
//   public function find_active_contacts_by_company(int $company_id, ?string $role = null): array;

//   /**
//    * Lấy danh sách quan hệ theo company với tuỳ chọn thực dụng:
//    * - include_inactive: bool (mặc định false)
//    * - role: ?string
//    * - orderby: id|is_primary|start_date|end_date|created_at|updated_at (mặc định is_primary)
//    * - order: ASC|DESC (mặc định DESC, riêng is_primary nên DESC trước)
//    * - per_page: int (mặc định 20)
//    * - page: int (mặc định 1)
//    */
//   public function find_by_company(int $company_id, array $args = []): array;

//   /**
//    * Đếm tổng số quan hệ theo cùng tiêu chí của find_by_company (phục vụ phân trang).
//    */
//   public function count_by_company(int $company_id, array $args = []): int;

//   /**
//    * Tìm quan hệ theo (company_id, contact_id). Hữu ích để kiểm tra trước khi attach.
//    */
//   public function find_relation_by_company_contact(int $company_id, int $contact_id): ?array;

//   /**
//    * Kiểm tra tồn tại contact (thường tách sang ContactRepositoryInterface,
//    * nhưng để thực dụng có thể giữ tạm ở đây).
//    */
//   public function contact_exists(int $contact_id): bool;
// }

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
}
