<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Modules\Note\Application\DTO\FileDTO;

final class FileService
{
    public function __construct(private FileRepositoryInterface $repo) {}

    public function attach_file(string $entity_type, int $entity_id, int $attachment_id, int $user_id): int
    {
        $dto                = new FileDTO();
        $dto->entity_type   = $entity_type;
        $dto->entity_id     = $entity_id;
        $dto->attachment_id = $attachment_id;
        $dto->uploaded_by   = $user_id;

        return $this->repo->attach($dto);
    }

    /** @return FileDTO[] */
    public function list_files(string $entity_type, int $entity_id): array
    {
        return $this->repo->find_by_entity($entity_type, $entity_id);
    }

    public function detach_file(int $id): void
    {
        $this->repo->detach($id);
    }
}
