<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

/**
 * Interface repository cho CredentialActivation
 * (Theo yêu cầu: đặt tại TMT\CRM\Domain\Repositories\)
 */
interface CredentialActivationRepositoryInterface
{
    public function find_by_id(int $id);
    public function list_by_credential(int $credential_id): array;
    public function create(object $dto): int;
    public function deactivate(int $id): bool;
    public function transfer(int $from_id, object $new_dto): int;
}
