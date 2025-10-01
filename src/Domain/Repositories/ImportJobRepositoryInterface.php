<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\ExportImport\Application\DTO\ImportJobDTO;

interface ImportJobRepositoryInterface
{
    public function create(ImportJobDTO $dto): int;
    public function find_by_id(int $id): ?ImportJobDTO;
    /** @return ImportJobDTO[] */
    public function list_recent(int $limit = 20): array;
    public function update(ImportJobDTO $dto): void;
    public function update_status(int $id, string $status, ?string $error_message = null): void;
    public function update_counters(int $id, int $total, int $success, int $error): void;
}
