<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note\Domain\Repositories;

use TMT\CRM\Modules\Note\Application\DTO\NoteDTO;

interface NoteRepositoryInterface
{
    public function add(NoteDTO $note): int;
    /** @return NoteDTO[] */
    public function find_by_entity(string $entity_type, int $entity_id): array;
    public function delete(int $id): void;
}
