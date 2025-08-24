<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\CompanyContactRoleDTO;

interface CompanyContactRoleRepositoryInterface
{
    /** Bắt đầu một vai trò (role) cho customer tại company */
    public function assign_role(CompanyContactRoleDTO $dto): int;

    /** Kết thúc vai trò */
    public function end_role(int $id, string $end_date): bool;

    /**
     * Lấy liên hệ đang đảm nhiệm 1 role tại công ty.
     * Trả về mảng ['id'=>role_id, 'role'=>..., 'customer'=>['id'=>..,'name'=>..,'phone'=>..,'email'=>..]]
     */
    public function get_active_by_role(int $company_id, string $role): ?array;

    /**
     * Liệt kê toàn bộ vai trò đang hiệu lực tại công ty.
     * Mỗi item: ['id'=>role_id,'role'=>..., 'customer'=>[...]]
     */
    public function list_active(int $company_id): array;
}
