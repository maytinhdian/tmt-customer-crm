<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;

interface CredentialRepositoryInterface
{
    /** Lấy 1 credential theo id (null nếu không có) */
    public function find_by_id(int $id): ?CredentialDTO;

    /** Tìm theo number (unique) */
    public function find_by_number(string $number): ?CredentialDTO;

    /**
     * Tìm kiếm/paginate
     * @return array{items: CredentialDTO[], total: int}
     */
    public function search(array $filter, int $page = 1, int $per_page = 20): array;

    /** Tạo mới, trả về id */
    public function create(CredentialDTO $dto): int;

    /** Cập nhật */
    public function update(int $id, CredentialDTO $dto): bool;

    /** Xóa mềm */
    public function soft_delete(int $id, ?int $deleted_by = null, ?string $reason = null): bool;

    /** Cập nhật secret (đã mã hóa) + mask nhanh */
    public function update_secrets(int $id, ?string $secret_primary_encrypted, ?string $secret_secondary_encrypted, ?string $secret_mask): bool;

    /** Đổi trạng thái */
    public function update_status(int $id, string $status): bool;

    /** Cập nhật ngày hết hạn */
    public function update_expires_at(int $id, ?string $expires_at): bool;
}
