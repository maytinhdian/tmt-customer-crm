<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Domain\Repositories\NoteRepositoryInterface;
use TMT\CRM\Application\DTO\NoteDTO;

final class WpdbNoteRepository implements NoteRepositoryInterface
{
    private string $table;

    public function __construct(private wpdb $db)
    {
        $this->table = $this->db->prefix . 'crm_notes';
    }

    public function add(NoteDTO $note): int
    {
        $ok = $this->db->insert(
            $this->table,
            [
                'entity_type' => $note->entity_type,
                'entity_id'   => $note->entity_id,
                'content'     => $note->content,
                'created_by'  => $note->created_by,
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%d', '%s', '%d', '%s']
        );
        if ($ok === false) {
            throw new \RuntimeException('Insert note failed: ' . $this->db->last_error);
        }
        return (int)$this->db->insert_id;
    }

    public function find_by_entity(string $entity_type, int $entity_id): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE entity_type=%s AND entity_id=%d ORDER BY created_at DESC, id DESC",
            $entity_type,
            $entity_id
        );
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $dto = new NoteDTO();
            $dto->id          = (int)$r['id'];
            $dto->entity_type = (string)$r['entity_type'];
            $dto->entity_id   = (int)$r['entity_id'];
            $dto->content     = (string)$r['content'];
            $dto->created_by  = (int)$r['created_by'];
            $dto->created_at  = (string)$r['created_at'];
            $out[] = $dto;
        }
        return $out;
    }

    public function delete(int $id): void
    {
        $ok = $this->db->delete($this->table, ['id' => $id], ['%d']);
        if ($ok === false) {
            throw new \RuntimeException('Delete note failed: ' . $this->db->last_error);
        }
    }
}
