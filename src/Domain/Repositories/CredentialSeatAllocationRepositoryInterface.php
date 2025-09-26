<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

/**
 * Interface repository cho CredentialSeatAllocation
 * (Theo yêu cầu: đặt tại TMT\CRM\Domain\Repositories\)
 */
interface CredentialSeatAllocationRepositoryInterface
{
    public function list_by_credential(int $credential_id): array;
    public function create(object $dto): int;
    public function update_quota(int $allocation_id, int $seat_quota): bool;
    public function delete(int $allocation_id): bool;
}
