<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Infrastructure\Persistence;

use wpdb;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;
use TMT\CRM\Core\Files\Domain\DTO\FileDTO;

final class WpdbFileRepository implements FileRepositoryInterface
{
    private string $table;

    public function __construct(private wpdb $db)
    {
        $this->table = $this->db->prefix . 'tmt_crm_files';
    }

    public function create(FileDTO $dto): int
    {
        $this->db->insert($this->table, [
            'entity_type'   => $dto->entityType,
            'entity_id'     => $dto->entityId,
            'storage'       => $dto->storage,
            'path'          => $dto->path,
            'original_name' => $dto->originalName,
            'mime'          => $dto->mime,
            'size_bytes'    => $dto->sizeBytes,
            'checksum'      => $dto->checksum,
            'version'       => $dto->version,
            'visibility'    => $dto->visibility,
            'uploaded_by'   => $dto->uploadedBy,
            'uploaded_at'   => $dto->uploadedAt,
            'meta'          => $dto->meta ? wp_json_encode($dto->meta) : null,
        ], [
            '%s','%d','%s','%s','%s','%s','%d','%s','%d','%s','%d','%s','%s'
        ]);

        return (int)$this->db->insert_id;
    }

    public function findById(int $id): ?FileDTO
    {
        $row = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1", $id
        ), ARRAY_A);

        return $row ? $this->map($row) : null;
    }

    public function findByEntity(string $entityType, int $entityId, bool $withDeleted = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE entity_type=%s AND entity_id=%d";
        if (!$withDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " ORDER BY uploaded_at DESC";

        $rows = $this->db->get_results($this->db->prepare($sql, $entityType, $entityId), ARRAY_A);
        return array_map(fn($r) => $this->map($r), $rows ?? []);
    }

    public function softDelete(int $id): bool
    {
        return (bool)$this->db->update($this->table, [
            'deleted_at' => current_time('mysql'),
        ], ['id' => $id], ['%s'], ['%d']);
    }

    public function restore(int $id): bool
    {
        return (bool)$this->db->update($this->table, [
            'deleted_at' => null,
        ], ['id' => $id], ['%s'], ['%d']);
    }

    public function purge(int $id): bool
    {
        return (bool)$this->db->delete($this->table, ['id' => $id], ['%d']);
    }

    public function updateMeta(int $id, array $meta): bool
    {
        return (bool)$this->db->update($this->table, [
            'meta' => wp_json_encode($meta),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id], ['%s','%s'], ['%d']);
    }

    public function bumpVersion(int $id): bool
    {
        return (bool)$this->db->query($this->db->prepare(
            "UPDATE {$this->table} SET version = version + 1, updated_at = %s WHERE id = %d",
            current_time('mysql'), $id
        ));
    }

    private function map(array $r): FileDTO
    {
        return new FileDTO(
            id: (int)$r['id'],
            entityType: (string)$r['entity_type'],
            entityId: (int)$r['entity_id'],
            storage: (string)$r['storage'],
            path: (string)$r['path'],
            originalName: (string)$r['original_name'],
            mime: (string)$r['mime'],
            sizeBytes: (int)$r['size_bytes'],
            checksum: $r['checksum'] !== null ? (string)$r['checksum'] : null,
            version: (int)$r['version'],
            visibility: (string)$r['visibility'],
            uploadedBy: (int)$r['uploaded_by'],
            uploadedAt: (string)$r['uploaded_at'],
            updatedAt: $r['updated_at'] ?: null,
            deletedAt: $r['deleted_at'] ?: null,
            meta: $r['meta'] ? (array)json_decode((string)$r['meta'], true) : []
        );
    }
}
