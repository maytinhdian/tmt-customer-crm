<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Application\DTO\NoteDTO;

interface NoteRepositoryInterface
{
    public function add(NoteDTO $note): int;
    /** @return NoteDTO[] */
    public function find_by_entity(string $entity_type, int $entity_id): array;
    public function delete(int $id): void;
}
