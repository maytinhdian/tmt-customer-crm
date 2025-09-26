<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Modules\Password\Domain\Entities\PasswordItem;

interface PasswordRepositoryInterface
{
    /** @return array{items: PasswordItem[], total: int} */
    public function list(array $filters, int $page, int $per_page): array;

    public function find(int $id): ?PasswordItem;

    public function insert(PasswordItem $entity): int;

    public function update(PasswordItem $entity): bool;

    public function soft_delete(int $id, int $deleted_by, ?string $reason = null): bool;

    public function restore(int $id): bool;
}
