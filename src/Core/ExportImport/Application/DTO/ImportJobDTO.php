<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\DTO;

final class ImportJobDTO
{
    public function __construct(
        public ?int $id,
        public string $entity_type,
        public string $source_file,
        public string $format, // csv,xlsx (MVP: csv)
        public array $mapping, // [source_col => target_field]
        public bool $has_header,
        public string $status, // pending, previewed, running, done, failed
        public int $created_by,
        public string $created_at,
        public ?string $finished_at = null,
        public ?string $error_message = null,
        public int $total_rows = 0,
        public int $success_rows = 0,
        public int $error_rows = 0,
    ) {}
}
