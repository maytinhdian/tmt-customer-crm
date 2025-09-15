<?php
// src/Application/DTO/UserDTO.php
declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Application\DTO;

use TMT\CRM\Shared\Traits\AsArrayTrait;

/**
 * DTO người dùng (owner)
 * - Dùng cho hiển thị Người phụ trách trong CompanyContactListTable
 * - Tối giản, chỉ chứa dữ liệu cần thiết cho màn hình hiện tại
 */

final class UserDTO
{
    use AsArrayTrait;

    /** ID user trong WP (`wp_users.ID`) */
    public int $id;

    /** Tên hiển thị (`wp_users.display_name`) */
    public string $display_name;

    /** Email người dùng (`wp_users.user_email`) */
    public ?string $email;

    /**
     * Số điện thoại lấy từ usermeta (vd: meta_key = 'owner_phone')
     * Nếu không sử dụng, để null.
     */
    public ?string $phone;

    public function __construct(
        int $id,
        string $display_name,
        ?string $email = null,
        ?string $phone = null
    ) {
        $this->id            = $id;
        $this->display_name  = $display_name;
        $this->email         = $email;
        $this->phone         = $phone;
    }

    /** Helper nhỏ: trả về chuỗi "Tên (#ID)" để tái sử dụng khi render */
    public function format_owner_label(): string
    {
        $name = $this->display_name !== '' ? $this->display_name : '—';
        return $name . ' #' . $this->id;
    }
}
