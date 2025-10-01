<?php
declare(strict_types=1);

namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\ExportImport\Application\DTO\MappingRuleDTO;

interface MappingRuleRepositoryInterface
{
    public function create(MappingRuleDTO $dto): int;
    public function update(MappingRuleDTO $dto): void;
    public function delete(int $id): void;
    public function find_by_id(int $id): ?MappingRuleDTO;
    /** @return MappingRuleDTO[] */
    public function list_by_entity(string $entity_type): array;
}
