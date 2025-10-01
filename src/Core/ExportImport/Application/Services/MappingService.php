<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\Services;

use TMT\CRM\Core\ExportImport\Application\DTO\MappingRuleDTO;
use TMT\CRM\Domain\Repositories\MappingRuleRepositoryInterface;

final class MappingService
{
    public function __construct(private MappingRuleRepositoryInterface $repo) {}

    /** @return MappingRuleDTO[] */
    public function list_profiles(string $entity_type): array
    {
        return $this->repo->list_by_entity($entity_type);
    }

    public function save_profile(MappingRuleDTO $dto): int
    {
        return $dto->id ? ($this->repo->update($dto) ?? 0) : $this->repo->create($dto);
    }
}
