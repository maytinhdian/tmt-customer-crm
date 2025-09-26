<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

/**
 * Interface repository cho Credential
 * (Theo yêu cầu: đặt tại TMT\CRM\Domain\Repositories\)
 */
interface CredentialRepositoryInterface
{
    public function find_by_id(int $id);
    public function search(array $filter): array;
    public function create(object $dto): int;
    public function update(int $id, object $dto): bool;
    public function soft_delete(int $id): bool;
}
