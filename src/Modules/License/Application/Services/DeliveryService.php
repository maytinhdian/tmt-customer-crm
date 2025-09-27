<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Services;

use TMT\CRM\Domain\Repositories\CredentialDeliveryRepositoryInterface;
use TMT\CRM\Modules\License\Application\DTO\CredentialDeliveryDTO;

/**
 * DeliveryService: ghi nhận bàn giao credential cho khách.
 */
final class DeliveryService
{
    public function __construct(
        private readonly CredentialDeliveryRepositoryInterface $delivery_repo
    ) {}

    public function log_delivery(CredentialDeliveryDTO $dto): int
    {
        if (!$dto->credential_id) return 0;
        return $this->delivery_repo->create($dto);
    }

    public function list_by_credential(int $credential_id): array
    {
        return $this->delivery_repo->list_by_credential($credential_id);
    }
}
