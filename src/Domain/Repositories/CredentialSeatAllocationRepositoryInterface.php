<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

interface CredentialSeatAllocationRepositoryInterface
{
    /** Danh sách allocation theo credential */
    public function list_by_credential(int $credential_id): array; // CredentialSeatAllocationDTO[]

    /** Lấy 1 allocation */
    public function find_by_id(int $allocation_id): ?CredentialSeatAllocationDTO;

    /** Tạo mới allocation */
    public function create(CredentialSeatAllocationDTO $dto): int;

    /** Cập nhật quota/status/note */
    public function update(int $allocation_id, CredentialSeatAllocationDTO $dto): bool;

    /** Xóa allocation */
    public function delete(int $allocation_id, ?int $deleted_by = null, ?string $reason = null): bool;

    /** Cập nhật seat_used (thường tính từ activations) */
    public function update_seat_used(int $allocation_id, int $seat_used): bool;
}
