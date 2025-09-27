<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Modules\License\Application\DTO\CredentialDeliveryDTO;

interface CredentialDeliveryRepositoryInterface
{
    /** Danh sách deliveries theo credential */
    public function list_by_credential(int $credential_id): array; // CredentialDeliveryDTO[]

    /** Ghi nhận một lần bàn giao */
    public function create(CredentialDeliveryDTO $dto): int;
}
