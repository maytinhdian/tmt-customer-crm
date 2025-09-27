<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Modules\License\Application\DTO\CredentialActivationDTO;

interface CredentialActivationRepositoryInterface
{
    /** Lấy 1 activation */
    public function find_by_id(int $id): ?CredentialActivationDTO;

    /** Danh sách theo credential */
    public function list_by_credential(int $credential_id): array; // CredentialActivationDTO[]

    /** Danh sách theo allocation */
    public function list_by_allocation(int $allocation_id): array; // CredentialActivationDTO[]

    /** Tạo activation */
    public function create(CredentialActivationDTO $dto): int;

    /** Deactivate 1 activation */
    public function deactivate(int $id, ?string $deactivated_at = null): bool;

    /**
     * Transfer: deactivate old + create new (trả về id new)
     * (Triển khai ở Application có thể gọi 2 hàm repo, nhưng giữ method này nếu cần atomic)
     */
    public function transfer(int $from_activation_id, CredentialActivationDTO $new_dto): int;

    /** Cập nhật last_seen_at khi nhận tín hiệu/ping */
    public function touch_last_seen(int $id, ?string $at = null): bool;
}
