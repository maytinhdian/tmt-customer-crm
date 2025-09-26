<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

/**
 * Interface repository cho CredentialDelivery
 * (Theo yêu cầu: đặt tại TMT\CRM\Domain\Repositories\)
 */
interface CredentialDeliveryRepositoryInterface
{
    public function list_by_credential(int $credential_id): array;
    public function create(object $dto): int;
}
