<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\ExportImport\Application\DTO\ExportJobDTO;

interface ExportJobRepositoryInterface
{
    public function create(ExportJobDTO $dto): int;
    public function find_by_id(int $id): ?ExportJobDTO;
    /** @return ExportJobDTO[] */
    public function list_recent(int $limit = 20): array;
    public function update_status(int $id, string $status, ?string $file_path = null, ?string $error_message = null): void;
}
