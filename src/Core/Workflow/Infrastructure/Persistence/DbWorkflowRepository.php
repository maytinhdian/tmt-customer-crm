<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Workflow\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\WorkflowRepositoryInterface;
use TMT\CRM\Core\Workflow\Domain\DTO\WorkflowDTO;

final class DbWorkflowRepository implements WorkflowRepositoryInterface
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'tmt_crm_workflows';
    }

    /** @return WorkflowDTO[] */
    public function list_all(bool $only_enabled = false): array
    {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table}";
        if ($only_enabled) {
            $sql .= " WHERE enabled = 1";
        }
        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];
        return array_map(fn($r) => $this->row_to_dto($r), $rows);
    }

    public function find_by_id(int $id): ?WorkflowDTO
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id=%d", $id), ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function find_by_slug(string $slug): ?WorkflowDTO
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE slug=%s", $slug), ARRAY_A);
        return $row ? $this->row_to_dto($row) : null;
    }

    public function save(WorkflowDTO $dto): int
    {
        global $wpdb;
        $data = [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'enabled' => $dto->enabled ? 1 : 0,
            'trigger_key' => $dto->trigger_key,
            'conditions_json' => wp_json_encode($dto->conditions),
            'actions_json' => wp_json_encode($dto->actions),
            'metadata_json' => wp_json_encode($dto->metadata),
        ];

        if ($dto->id) {
            $wpdb->update($this->table, $data, ['id' => $dto->id]);
            return (int)$dto->id;
        } else {
            $wpdb->insert($this->table, $data);
            return (int)$wpdb->insert_id;
        }
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return (bool)$wpdb->delete($this->table, ['id' => $id]);
    }

    private function row_to_dto(array $row): WorkflowDTO
    {
        return WorkflowDTO::from_array([
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'slug' => (string)$row['slug'],
            'enabled' => (bool)$row['enabled'],
            'trigger_key' => (string)$row['trigger_key'],
            'conditions' => json_decode((string)($row['conditions_json'] ?? '[]'), true) ?: [],
            'actions' => json_decode((string)($row['actions_json'] ?? '[]'), true) ?: [],
            'metadata' => json_decode((string)($row['metadata_json'] ?? '[]'), true) ?: [],
        ]);
    }
}
