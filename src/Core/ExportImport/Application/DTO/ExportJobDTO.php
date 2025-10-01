<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\DTO;

final class ExportJobDTO
{
    public function __construct(
        public ?int $id,
        public string $entity_type,
        public array $filters,
        public array $columns,
        public string $format, // csv,xlsx (MVP: csv)
        public string $status, // pending, running, done, failed
        public ?string $file_path,
        public int $created_by,
        public string $created_at,
        public ?string $finished_at = null,
        public ?string $error_message = null,
    ) {}

    public static function new_pending(string $entity_type, array $filters, array $columns, int $user_id, string $format = 'csv'): self
    {
        return new self(
            id: null,
            entity_type: $entity_type,
            filters: $filters,
            columns: $columns,
            format: $format,
            status: 'pending',
            file_path: null,
            created_by: $user_id,
            created_at: gmdate('Y-m-d H:i:s')
        );
    }
}
