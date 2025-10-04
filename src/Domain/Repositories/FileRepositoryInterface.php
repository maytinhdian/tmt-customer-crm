<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Files\Application\DTO\FileDTO;

interface FileRepositoryInterface
{
    /** @return int ID vừa tạo */
    public function create(FileDTO $dto): int;

    /** @return ?FileDTO */
    public function find_by_id(int $id): ?FileDTO;

    /** @return FileDTO[] */
    public function find_by_entity(string $entity_type, int $entity_id): array;

    public function delete(int $id): bool;
}
