<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

interface DeliveryRepositoryInterface
{
    public function create(DeliveryDTO $dto): int;
    /** @return DeliveryDTO[] */
    public function find_unread_for_user(int $user_id, int $limit = 20): array;
    public function mark_read(int $delivery_id, int $user_id): bool;
    public function update_status(int $delivery_id, string $status, ?string $error = null): bool;
}
