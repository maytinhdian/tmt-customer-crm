<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Core\Files\Application\DTO\FileDTO;
use TMT\CRM\Domain\Repositories\FileRepositoryInterface;

final class WpdbFileRepository implements FileRepositoryInterface
{
    public function __construct(private wpdb $db) {}

    private function table(): string
    {
        return $this->db->prefix . 'tmt_crm_files';
    }

    public function create(FileDTO $dto): int
    {
        $this->db->insert($this->table(), [
            'entity_type'   => $dto->entity_type,
            'entity_id'     => $dto->entity_id,
            'attachment_id' => $dto->attachment_id,
            'uploaded_by'   => $dto->uploaded_by,
            'uploaded_at'   => $dto->uploaded_at ?: current_time('mysql'),
        ], [
            '%s','%d','%d','%d','%s'
        ]);

        return (int) $this->db->insert_id;
    }

    public function find_by_id(int $id): ?FileDTO
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ? FileDTO::from_array($row) : null;
    }

    /** @return FileDTO[] */
    public function find_by_entity(string $entity_type, int $entity_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table()} WHERE entity_type = %s AND entity_id = %d ORDER BY id DESC",
                $entity_type, $entity_id
            ),
            ARRAY_A
        );
        return array_map(static fn(array $r) => FileDTO::from_array($r), $rows ?: []);
    }

    public function delete(int $id): bool
    {
        $deleted = $this->db->delete($this->table(), ['id' => $id], ['%d']);
        return (bool) $deleted;
    }
}
