<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\MappingRuleRepositoryInterface;
use TMT\CRM\Core\ExportImport\Application\DTO\MappingRuleDTO;

final class WpdbMappingRuleRepository implements MappingRuleRepositoryInterface
{
    public function __construct(private \wpdb $db) {}
    private function table(): string { return $this->db->prefix . 'tmt_crm_mapping_rules'; }

    public function create(MappingRuleDTO $dto): int
    {
        $this->db->insert($this->table(), [
            'entity_type' => $dto->entity_type,
            'profile_name'=> $dto->profile_name,
            'mapping'     => wp_json_encode($dto->mapping),
            'owner_id'    => $dto->owner_id,
            'created_at'  => $dto->created_at,
        ]);
        return (int) $this->db->insert_id;
    }

    public function update(MappingRuleDTO $dto): void
    {
        $this->db->update($this->table(), [
            'profile_name'=> $dto->profile_name,
            'mapping'     => wp_json_encode($dto->mapping),
            'updated_at'  => gmdate('Y-m-d H:i:s'),
        ], ['id' => $dto->id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete($this->table(), ['id' => $id]);
    }

    public function find_by_id(int $id): ?MappingRuleDTO
    {
        $r = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id), ARRAY_A);
        if (!$r) { return null; }
        return new MappingRuleDTO(
            id: (int)$r['id'],
            entity_type: (string)$r['entity_type'],
            profile_name: (string)$r['profile_name'],
            mapping: (array) json_decode((string)$r['mapping'], true),
            owner_id: (int)$r['owner_id'],
            created_at: (string)$r['created_at'],
            updated_at: $r['updated_at'],
        );
    }

    public function list_by_entity(string $entity_type): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT id FROM {$this->table()} WHERE entity_type=%s ORDER BY id DESC", $entity_type), ARRAY_A);
        return array_values(array_filter(array_map(fn($r) => $this->find_by_id((int)$r['id']), $rows)));
    }
}
