<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Domain\Repositories;

use TMT\CRM\Core\Files\Domain\DTO\FileDTO;

interface FileRepositoryInterface
{
    public function create(FileDTO $dto): int;
    public function findById(int $id): ?FileDTO;

    /** @return FileDTO[] */
    public function findByEntity(string $entityType, int $entityId, bool $withDeleted = false): array;

    public function softDelete(int $id): bool;
    public function restore(int $id): bool;
    public function purge(int $id): bool;

    public function updateMeta(int $id, array $meta): bool;
    public function bumpVersion(int $id): bool;
}
