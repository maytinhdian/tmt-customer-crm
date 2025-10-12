<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Workflow\Domain\DTO\WorkflowDTO;

/**
 * Interface cho Workflow Repository (namespace chung của dự án).
 */
interface WorkflowRepositoryInterface
{
    /** @return WorkflowDTO[] */
    public function list_all(bool $only_enabled = false): array;

    public function find_by_id(int $id): ?WorkflowDTO;

    public function find_by_slug(string $slug): ?WorkflowDTO;

    public function save(WorkflowDTO $dto): int;

    public function delete(int $id): bool;
}
