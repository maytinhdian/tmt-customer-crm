<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Domain\Repositories;

use TMT\CRM\Modules\Note\Application\DTO\FileDTO;

interface FileRepositoryInterface
{
    public function attach(FileDTO $file): int;
    /** @return FileDTO[] */
    public function find_by_entity(string $entity_type, int $entity_id): array;
    public function detach(int $id): void;
}
