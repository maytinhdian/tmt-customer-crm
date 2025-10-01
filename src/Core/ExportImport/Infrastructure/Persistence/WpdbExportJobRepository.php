<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\ExportJobRepositoryInterface;
use TMT\CRM\Core\ExportImport\Application\DTO\ExportJobDTO;

final class WpdbExportJobRepository implements ExportJobRepositoryInterface
{
    public function __construct(private \wpdb $db) {}

    private function table(): string { return $this->db->prefix . 'tmt_crm_export_jobs'; }

    public function create(ExportJobDTO $dto): int
    {
        $this->db->insert($this->table(), [
            'entity_type' => $dto->entity_type,
            'filters'     => wp_json_encode($dto->filters),
            'columns'     => wp_json_encode($dto->columns),
            'format'      => $dto->format,
            'status'      => $dto->status,
            'file_path'   => $dto->file_path,
            'error_message' => $dto->error_message,
            'created_by'  => $dto->created_by,
            'created_at'  => $dto->created_at,
            'finished_at' => $dto->finished_at,
        ]);
        return (int) $this->db->insert_id;
    }

    public function find_by_id(int $id): ?ExportJobDTO
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id), ARRAY_A);
        if (!$row) { return null; }
        return new ExportJobDTO(
            id: (int)$row['id'],
            entity_type: (string)$row['entity_type'],
            filters: (array) json_decode((string)$row['filters'], true),
            columns: (array) json_decode((string)$row['columns'], true),
            format: (string)$row['format'],
            status: (string)$row['status'],
            file_path: $row['file_path'],
            created_by: (int)$row['created_by'],
            created_at: (string)$row['created_at'],
            finished_at: $row['finished_at'],
            error_message: $row['error_message'],
        );
    }

    public function list_recent(int $limit = 20): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT id FROM {$this->table()} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
        return array_values(array_filter(array_map(fn($r) => $this->find_by_id((int)$r['id']), $rows)));
    }

    public function update_status(int $id, string $status, ?string $file_path = null, ?string $error_message = null): void
    {
        $this->db->update($this->table(), [
            'status' => $status,
            'file_path' => $file_path,
            'error_message' => $error_message,
            'finished_at' => in_array($status, ['done','failed'], true) ? gmdate('Y-m-d H:i:s') : null,
        ], ['id' => $id]);
    }
}
