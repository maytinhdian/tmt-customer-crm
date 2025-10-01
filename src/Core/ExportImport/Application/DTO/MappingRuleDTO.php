<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\DTO;

final class MappingRuleDTO
{
    public function __construct(
        public ?int $id,
        public string $entity_type, // company, customer, contact, ...
        public string $profile_name,
        public array $mapping, // [source_col => target_field]
        public int $owner_id,
        public string $created_at,
        public ?string $updated_at = null,
    ) {}
}
