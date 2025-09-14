<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Application\Services;

use TMT\CRM\Modules\Note\Domain\Repositories\NoteRepositoryInterface;
use TMT\CRM\Modules\Note\Application\DTO\NoteDTO;

final class NoteService
{
    public function __construct(private NoteRepositoryInterface $repo) {}

    public function add_note(string $entity_type, int $entity_id, string $content, int $user_id): int
    {
        $dto               = new NoteDTO();
        $dto->entity_type  = $entity_type;
        $dto->entity_id    = $entity_id;
        $dto->content      = $content;
        $dto->created_by   = $user_id;

        return $this->repo->add($dto);
    }

    /** @return NoteDTO[] */
    public function list_notes(string $entity_type, int $entity_id): array
    {
        return $this->repo->find_by_entity($entity_type, $entity_id);
    }

    public function delete_note(int $id): void
    {
        $this->repo->delete($id);
    }
}
