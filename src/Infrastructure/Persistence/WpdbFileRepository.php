<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Application\DTO\FileDTO;

final class WpdbFileRepository implements FileRepositoryInterface
{
    private string $table;

    public function __construct(private wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_files';
    }

    public function attach(FileDTO $file): int
    {
        $ok = $this->db->insert(
            $this->table,
            [
                'entity_type'   => $file->entity_type,
                'entity_id'     => $file->entity_id,
                'attachment_id' => $file->attachment_id,
                'uploaded_by'   => $file->uploaded_by,
                'uploaded_at'   => current_time('mysql'),
            ],
            ['%s', '%d', '%d', '%d', '%s']
        );
        if ($ok === false) {
            throw new \RuntimeException('Insert file failed: ' . $this->db->last_error);
        }
        return (int)$this->db->insert_id;
    }

    public function find_by_entity(string $entity_type, int $entity_id): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE entity_type=%s AND entity_id=%d ORDER BY uploaded_at DESC, id DESC",
            $entity_type,
            $entity_id
        );
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $dto = new FileDTO();
            $dto->id            = (int)$r['id'];
            $dto->entity_type   = (string)$r['entity_type'];
            $dto->entity_id     = (int)$r['entity_id'];
            $dto->attachment_id = (int)$r['attachment_id'];
            $dto->uploaded_by   = (int)$r['uploaded_by'];
            $dto->uploaded_at   = (string)$r['uploaded_at'];
            $out[] = $dto;
        }
        return $out;
    }

    public function detach(int $id): void
    {
        $ok = $this->db->delete($this->table, ['id' => $id], ['%d']);
        if ($ok === false) {
            throw new \RuntimeException('Delete file failed: ' . $this->db->last_error);
        }
    }
}
