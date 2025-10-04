<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Domain\Entities;

final class File
{
    public function __construct(
        public int $id,
        public string $entity_type,
        public int $entity_id,
        public int $attachment_id,
        public int $uploaded_by,
        public string $uploaded_at
    ) {}
}
