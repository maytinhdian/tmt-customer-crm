<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Notifications\Domain\DTO\NotificationDTO;

interface NotificationRepositoryInterface
{
    public function create(NotificationDTO $dto): int;
    public function find(int $id): ?NotificationDTO;
}
