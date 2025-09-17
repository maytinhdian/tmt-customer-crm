<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Domain\Contracts;

interface SoftDeletableRepositoryInterface
{
    public function mark_deleted(int $id, int $actor_id, ?string $reason = null): void;
    public function restore(int $id, int $actor_id): void;
    public function purge(int $id, int $actor_id): void;
    public function exists_active(int $id): bool;
}
