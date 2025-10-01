<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\ImportJobRepositoryInterface;
use TMT\CRM\Core\ExportImport\Application\DTO\ImportJobDTO;

final class WpdbImportJobRepository implements ImportJobRepositoryInterface
{
    public function __construct(private \wpdb $db) {}
    private function table(): string { return $this->db->prefix . 'tmt_crm_import_jobs'; }

    public function create(ImportJobDTO $dto): int
    {
        $this->db->insert($this->table(), [
            'entity_type' => $dto->entity_type,
            'source_file' => $dto->source_file,
            'format'      => $dto->format,
            'mapping'     => wp_json_encode($dto->mapping),
            'has_header'  => $dto->has_header ? 1 : 0,
            'status'      => $dto->status,
            'created_by'  => $dto->created_by,
            'created_at'  => $dto->created_at,
        ]);
        return (int) $this->db->insert_id;
    }

    public function find_by_id(int $id): ?ImportJobDTO
    {
        $r = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE id=%d", $id), ARRAY_A);
        if (!$r) { return null; }
        return new ImportJobDTO(
            id: (int)$r['id'],
            entity_type: (string)$r['entity_type'],
            source_file: (string)$r['source_file'],
            format: (string)$r['format'],
            mapping: (array) json_decode((string)$r['mapping'], true),
            has_header: (bool)$r['has_header'],
            status: (string)$r['status'],
            created_by: (int)$r['created_by'],
            created_at: (string)$r['created_at'],
            finished_at: $r['finished_at'],
            error_message: $r['error_message'],
            total_rows: (int)($r['total_rows'] ?? 0),
            success_rows: (int)($r['success_rows'] ?? 0),
            error_rows: (int)($r['error_rows'] ?? 0),
        );
    }

    public function list_recent(int $limit = 20): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT id FROM {$this->table()} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
        return array_values(array_filter(array_map(fn($r) => $this->find_by_id((int)$r['id']), $rows)));
    }

    public function update(ImportJobDTO $dto): void
    {
        $this->db->update($this->table(), [
            'mapping'    => wp_json_encode($dto->mapping),
            'status'     => $dto->status,
            'finished_at'=> $dto->finished_at,
            'error_message'=> $dto->error_message,
            'total_rows' => $dto->total_rows,
            'success_rows'=> $dto->success_rows,
            'error_rows' => $dto->error_rows,
        ], ['id' => $dto->id]);
    }

    public function update_status(int $id, string $status, ?string $error_message = null): void
    {
        $this->db->update($this->table(), [
            'status' => $status,
            'error_message' => $error_message,
            'finished_at' => in_array($status, ['done','failed'], true) ? gmdate('Y-m-d H:i:s') : null,
        ], ['id' => $id]);
    }

    public function update_counters(int $id, int $total, int $success, int $error): void
    {
        $this->db->update($this->table(), [
            'total_rows' => $total,
            'success_rows' => $success,
            'error_rows' => $error,
        ], ['id' => $id]);
    }
}
